<?php
/**
 * Nuwani PHP IRC Bot Framework
 * Copyright (c) 2006-2010 The Nuwani Project
 *
 * Nuwani is a framework for IRC Bots built using PHP. Nuwani speeds up bot
 * development by handling basic tasks as connection- and bot management, timers
 * and module managing. Features for your bot can easily be added by creating
 * your own modules, which will receive callbacks from the framework.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright (c) 2006-2010 The Nuwani Project
 * @package Ignore Module
 * @author Peter Beverloo <peter@lvp-media.com>
 * @author Dik Grapendaal <dik@sa-mp.nl>
 * @see http://nuwani.googlecode.com
 */

use Nuwani \ ModuleManager;

class Ignore extends ModuleBase {

        /**
         * This array contains all the patterns to ignore. These are matched
         * against the usermasks of incoming messages.
         *
         * @var array
         */

        private $m_aIgnored;

        /**
         * The constructor will load up all the ignored people.
         */

        public function __construct ()
        {
                $this -> loadIgnored ();
        }

        /**
         * The destructor will quickly save everything before the bot shuts down.
         */
        public function __destruct ()
        {
                //$this -> saveIgnored ();
        }

        /**
         * This method loads all the ignored patterns from Ignore.dat.
         */

        public function loadIgnored ()
        {
                if (file_exists ('Data/Ignore.dat'))
                {
                        $this -> m_aIgnored = unserialize (file_get_contents ('Data/Ignore.dat'));
                }
                else
                {
                        $this -> m_aIgnored = array ();
                }
        }

        /**
         * This method saves all ignored patterns to Ignore.dat.
         */

        public function saveIgnored ()
        {
                file_put_contents ('Data/Ignore.dat', serialize ($this -> m_aIgnored));
        }

        /**
         * This method will add the given pattern to the array with all ignored
         * patterns.
         *
         * @param string $sIgnore The pattern to ignore.
         * @return boolean False is the pattern was already ignored.
         */

        private function addIgnore ($sIgnore)
        {
                if ($this -> isIgnored ($sIgnore))
                {
                        return false;
                }
                $this -> m_aIgnored [] = $sIgnore;

                return true;
        }

        /**
         * Removes the given pattern from the ignored list.
         *
         * @param string $sIgnore The pattern to remove.
         * @return boolean False if the pattern was not ignored.
         */

        private function removeIgnore ($sIgnore)
        {
                if (!$this -> isIgnored ($sIgnore))
                {
                        return false;
                }
                unset ($this -> m_aIgnored [array_search ($sIgnore, $this -> m_aIgnored)]);

                return true;
        }

        /**
         * Checks if the given pattern is ignored.
         *
         * @param string $sIgnore The pattern to check.
         * @return boolean
         */

        private function isIgnored ($sIgnore)
        {
                return in_array ($sIgnore, $this -> m_aIgnored);
        }

        /**
         * This method returns the list of all ignored patterns.
         *
         * @return array
         */

        public function getIgnored ()
        {
                return $this -> m_aIgnored;
        }

        /**
         * This method takes a Bot instance, and checks with the Evaluation
         * module if the current message being processed, is our owner.
         *
         * @param Bot $pBot The bot with the received message.
         * @return boolean
         */
        public function isBotOwner (Nuwani\Bot $pBot)
        {
                $pEval = ModuleManager :: getInstance () -> offsetGet ('Evaluation');
                if ($pEval -> checkSecurity ($pBot, 9999))
                {
                        return true;
                }

                return false;
        }

        /**
         * This method loops through all ignored patterns and tries to match the
         * given mask against every one of them, until a match is found. This
         * method could be extended in the future with more advanced matching
         * techniques.
         *
         * @param string $sMask The mask to match against.
         * @return boolean
         */
        private function checkIgnore ($sMask)
        {
                foreach ($this -> m_aIgnored as $sIgnore)
                {
                        if (stripos ($sMask, $sIgnore) !== false)
                        {
                                return true;
                        }
                }

                return false;
        }

        /**
         * This method returns true when a command has properly been handled,
         * false when nothing has been done, and ModuleManager :: FINISHED when
         * the message should be ignored.
         *
         * @param Bot $pBot The current bot.
         * @param string $sMessage The message containing a command (or not).
         * @param string $sDest The destination to send the messages to.
         * @return mixed
         */
        private function handleCommand (Nuwani\Bot $pBot, $sMessage, $sDest)
        {
                if (!$this -> isBotOwner ($pBot))
                {
                        /** Can't ignore the bot owner, that would fuck things up. **/
                        $sMask = $pBot -> In -> User;
                        if ($this -> checkIgnore ($sMask))
                        {
                                return ModuleManager :: FINISHED;
                        }
                        else
                        {
                                return true;
                        }
                }
                else
                {
                        /** Commands for the bot owner. **/
                        if (strpos ($sMessage, ' ') === false)
                        {
                                $sTrigger = $sMessage;
                                $sParams = null;
                        }
                        else
                        {
                                list ($sTrigger, $sParams) = explode (' ', $sMessage, 2);
                                $sParams = trim ($sParams);
                        }

                        switch ($sTrigger)
                        {
                                case '!ignore':
                                {
                                        if ($sParams == null)
                                        {
                                                $sMsg = '3Usage: !ignore Pattern';
                                        }
                                        else
                                        {
                                                if ($this -> addIgnore ($sParams))
                                                {
                                                        $sMsg = '3Success: *' . $sParams . '* is now ignored.';
                                                        $this -> saveIgnored ();
                                                }
                                                else
                                                {
                                                        $sMsg = '4Error: *' . $sParams . '* is already ignored.';
                                                }
                                        }

                                        break;
                                }

                                case '!unignore':
                                {
                                        if ($sParams == null)
                                        {
                                                $sMsg = '3Usage: !unignore Pattern';
                                        }
                                        else
                                        {
                                                if ($this -> removeIgnore ($sParams))
                                                {
                                                        $sMsg = '3Success: *' . $sParams . '* is not ignored anymore.';
                                                        $this -> saveIgnored ();
                                                }
                                                else
                                                {
                                                        $sMsg = '4Error: *' . $sParams . '* is not ignored.';
                                                }
                                        }

                                        break;
                                }

                                case '!ignored':
                                {
                                        $sMsg = '3Ignored: ';

                                        foreach ($this -> getIgnored () as $sIgnore)
                                        {
                                                $sMsg .= '*' . $sIgnore . '*, ';
                                        }

                                        if (strlen ($sMsg) > 12)
                                        {
                                                $sMsg = substr ($sMsg, 0, -2);
                                        }
                                        else
                                        {
                                                $sMsg .= '14None';
                                        }

                                        break;
                                }
                        }
                }

                if (isset ($sMsg))
                {
                        $pBot -> send ('PRIVMSG ' . $sDest . ' :' . $sMsg);
                        return true;
                }

                return false;
        }

        /**
         * This method allows us to ignore channel PRIVMSGs from the ignored users.
         *
         * @param Bot $pBot The bot which received this message.
         * @param string $sChannel The channel this message was spammed in
         * @param string $sNickname Nickname who is messaging us (or the channel).
         * @param string $sMessage The message being send to us.
         */
        public function onChannelPrivmsg (Nuwani\Bot $pBot, $sChannel, $sNickname, $sMessage)
        {
                return $this -> handleCommand ($pBot, $sMessage, $sChannel);
        }

        /**
         * We will also ignore notices, because those can used to provide commands
         * to the bot as well.
         *
         * @param Bot $pBot The bot which received this message.
         * @param string $sChannel The channel this message was spammed in
         * @param string $sNickname Nickname who is messaging us (or the channel).
         * @param string $sMessage The message being send to us.
         */
        public function onNotice (Nuwani\Bot $pBot, $sChannel, $sNickname, $sMessage)
        {
                return $this -> handleCommand ($pBot, $sMessage, $sNickname);
        }

        /**
         * Well, queries should be ignored as well.
         *
         * @param Bot $pBot The bot which received this message.
         * @param string $sNickname Nickname who is PM'ing us.
         * @param string $sMessage The message being send to us.
         */
        public function onPrivmsg (Nuwani\Bot $pBot, $sNickname, $sMessage)
        {
                if (substr ($sMessage, 0, 6) == 'login ' && $this -> checkIgnore ($pBot -> In -> User))
                {
                        /** Directed at the Evaluation module, always allow this. **/
                        $pEval = ModuleManager :: getInstance () -> offsetGet ('Evaluation');
                        if ($pEval instanceof ModuleBase)
                        {
                                $pEval -> onPrivmsg ($pBot, $sNickname, $sMessage);
                        }
                }

                /** Ignore all other messages, but only if this person's ignored of course. **/
                return $this -> handleCommand ($pBot, $sMessage, $sNickname);
        }
}
?>
