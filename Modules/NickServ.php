<?php
/**
 * NickServ Nuwani Module
 *
 * This module extends the Nuwani v2 bot with a couple of NickServ capabilities. This includes auto-identifying, but
 * also logging in and out via a command, in case something goes wrong.
 *
 * Failed attempts get echoed back to the owner(s).
 *
 * @copyright Copyright (c) 2011 The Nuwani Project, http://nuwani.googlecode.com/
 * @author Dik Grapendaal <dik@sa-mp.nl>
 * @version $Id$
 * @package Modules
 */

use Nuwani\Bot;
use Nuwani\Configuration;

class NickServ extends ModuleBase {
    /**
     * The nickname of the authentication service.
     *
     * @var string
     */
    const NICKSERV_NICKNAME = 'NickServ';

    /**
     * The values of the Logger module level constants we use. Added in here because we can't be sure of the Logger
     * module's existance.
     *
     * @var integer
     */
    const LOGGER_LEVEL_INFO = 2;
    const LOGGER_LEVEL_ERROR = 4;

    /**
     * In order to keep track of the bots we've already tried to identify and are still awaiting response, this array
     * is used.
     *
     * @var array
     */
    private $attempedIdentify = array();

    /**
     * This property will be filled with a nickname when an owner executes !identify, and thus wants to be notified of
     * the result.
     *
     * @var string
     */
    private $notifySuccess = '';

    /**
     * This function will be called whenever a new module is being loaded. If the Commands module is loaded, we
     * register the commands in this module.
     *
     * @param ModuleBase $module The module that is being loaded.
     */
    public function onModuleLoad(ModuleBase $module) {
        if (get_class ($module) == 'Commands') {
            // Added a ns prefix to the commands because the commands would be too generic otherwise.
            $module -> registerCommand (new Command ('nsidentify', array ($this, 'handleIdentifyCommand'), 'owner'));
            $module -> registerCommand (new Command ('nslogout', array ($this, 'handleLogoutCommand'), 'owner'));
        }
    }

    /**
     * Send the IDENTIFY message including the password for the given bot to NickServ.
     *
     * @param Bot $bot The bot to identify with NickServ.
     * @return boolean Indicates whether a password was found and sent.
     */
    private function identify(Bot $bot) {
        if (isset($bot['Password']) && $bot['Password'] != '') {
            $bot -> send('PRIVMSG ' . self :: NICKSERV_NICKNAME . ' :IDENTIFY ' . $bot['Password'], true);
            $this -> attempedIdentify[$bot['Network'] . $bot['Nickname']] = time();
            return true;
        }
        return false;
    }

    /**
     * This method notifies all the configured owners in case of an error and whatnot. If the Logger module is loaded,
     * that will be used, since that module has more destination options than we want to build in.
     *
     * @param Bot $bot The bot to send the message with.
     * @param string $message The message to send.
     * @param int $level The optional logging level.
     */
    private function notifyOwners(Bot $bot, $message, $level = self :: LOGGER_LEVEL_INFO) {
        if ($this -> notifySuccess != '') {
            $bot -> send ('PRIVMSG ' . $this -> notifySuccess . ' :' . $message);
        }

        if (class_exists('Logger')) {
            Logger :: log($this, $message, $level);
        } else {
            // FIXME This really fucks up when there are different owners for different networks. More of a Nuwani
            // problem though.
            $configuration = Configuration :: getInstance() -> get('Owners');
            foreach ($configuration as $owner) {
                $nickname = substr($owner['Username'], 0, strpos($owner['Username'], '!'));

                if ($nickname != '') {
                    $bot -> send('PRIVMSG ' . $nickname . ' :' . $message);
                }
            }
        }
    }

    /**
     * Handles the !nsidentify command.
     *
     * @param Bot $bot The bot which received the command.
     * @param string $sDestination The channel or nickname where the output should go.
     * @param string $channel The channel in which we received the command.
     * @param string $sNickname The person who activated the command.
     * @param array $params The parameters of the command, split by whitespace.
     * @param string $sMessage The complete string following the command trigger.
     */
    public function handleIdentifyCommand(Bot $bot, $sDestination, $channel, $nickname, $params, $message) {
        $this -> notifySuccess = $nickname;
        $this -> identify($bot);
    }

    /**
     * Handles the !nslogout command.
     *
     * @param Bot $bot The bot which received the command.
     * @param string $sDestination The channel or nickname where the output should go.
     * @param string $channel The channel in which we received the command.
     * @param string $sNickname The person who activated the command.
     * @param array $params The parameters of the command, split by whitespace.
     * @param string $sMessage The complete string following the command trigger.
     */
    public function handleLogoutCommand(Bot $bot, $sDestination, $channel, $nickname, $params, $message) {
        $bot -> send('PRIVMSG ' . self :: NICKSERV_NICKNAME . ' :LOGOUT');
    }

    /**
     * Handles a message received from NickServ and replies accordingly.
     *
     * @param Bot $bot
     * @param string $message
     */
    private function handleNickServMessage(Bot $bot, string $nickname, $message) {
        if (strpos($message, 'registered and protected') !== false) {
            if (!isset($this -> attemptedIdentify[$bot['Network'] . $bot['Nickname']])) {
                $this -> identify($bot);
            }
        } else {
            if ($nickname == self::NICKSERV_NICKNAME) {
                switch (trim($message)) {
                    case 'Password accepted - you are now recognized.':
                        if ($this -> notifySuccess != '') {
                            // Used !identify, so the user expects a response.
                            $this -> notifyOwners($bot, 'Successfully identified with NickServ.');
                            $this -> notifySuccess = '';
                        }

                        if (isset($this -> attemptedIdentify[$bot['Network'] . $bot['Nickname']])) {
                            unset($this -> attemptedIdentify[$bot['Network'] . $bot['Nickname']]);
                        }

                        break;

                    case 'Password incorrect.':
                        $this -> notifyOwners($bot, 'NickServ password for ' . $bot['Nickname'] . ' incorrect.',
                            self :: LOGGER_LEVEL_ERROR);
                        break;

                    case 'Your nick isn\'t registered.':
                        $this -> notifyOwners($bot, 'Nickname ' . $bot['Nickname'] . ' is not registered with NickServ.',
                            self :: LOGGER_LEVEL_ERROR);
                        break;
                }
            }

            // Always join the channels, no matter what the response was.
            if (isset($bot['OnConnect']['Channels'])) {
                $this->joinChannels($bot);
            }
        }
    }

    /**
     * Joins the channels as specified in the Bot's configuration.
     *
     * @param Bot $bot The bot to join the channels for.
     */
    private function joinChannels(Bot $bot) {
        foreach ($bot['OnConnect']['Channels'] as $channel) {
            $bot->send('JOIN ' . $channel);
        }
    }

    /**
     * This method also tries to quickly identify if a password was found for the given Bot.
     *
     * @param Bot $bot
     */
    public function onConnect(Bot $bot) {
        $this -> identify ($bot);
    }

    /**
     * This method fetches all the notices from NickServ and handles them appropriately. Commands from the owner(s)
     * are processed in here as well.
     *
     * @param Bot $bot The bot which received the message.
     * @param string $channel Channel in which we received the message.
     * @param string $nickname The nickname associated with this message.
     * @param string $message The actual message we received.
     */
    public function onNotice(Bot $bot, $channel, $nickname, $message) {
        $this -> handleNickServMessage($bot, $nickname, $message);
    }

    /**
     * In case NickServ is configured to send normal private messages instead of notices, we got that covered too.
     *
     * @param Bot $bot The bot which received the message.
     * @param string $nickname The nickname associated with this message.
     * @param string $message The actual message received.
     */
    public function onPrivmsg(Bot $bot, $nickname, $message) {
        $this -> handleNickServMessage($bot, $nickname, $message);
    }
}
