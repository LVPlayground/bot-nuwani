<?php
// Lost Venturas Playground <http://jcmp.nl/>
// Copyright (c) 2014 The Lost Venturas Playground authors. All rights reserved.
//
// Licensed under the MIT license, a copy of which is available in the LICENSE file.

require_once __DIR__ . '/Commands.php';
require_once __DIR__ . '/MessagePipe.php';

use JustCause \ Commands;
use JustCause \ MessagePipe;
use JustCause \ MessagePipeListener;
use Nuwani \ Bot;
use Nuwani \ BotManager;

// Nuwani 2 module for supporting the Just Cause 2: Multiplayer server.
class JustCause extends ModuleBase implements MessagePipeListener {
    // Port on which to distribute commands intended for the server.
    const CommandPort = 27122;

    // Address to which to distribute commands intended for the server.
    const CommandAddress = '127.0.0.1';

    // Port on which to listen for incoming echo messages.
    const DistributionPort = 27121;

    // Channel in which messages will be outputted.
    const EchoChannel = '#LVP.JCMP';
    const CommandPrefix = '!';

    // Pipe with which we're listening to the server.
    private $m_serverPipe;

    // Instance of the command handler to which we'll distribute commands.
    private $m_commands;

    // Message filters through which incoming messages will be routed (like the SA-MP server).
    private $m_messageFilters;

    // Queue of messages which have yet to be send to the echo channel.
    private $m_messageQueue;

    public function __construct() {
        $this->m_serverPipe = new MessagePipe(self::CommandPort, self::CommandAddress, self::DistributionPort);
        $this->m_commands = new Commands($this->m_serverPipe, $this);
        $this->m_messageQueue = array();

        $this->reloadMessageFilters();
    }

    // Called when an echo message has been received from the JC-MP Server.
    public function onMessageReceived($message, $address, $port) {
        if (substr($message, 0, 1) != '{')
            return; // not a JSON message.

        $parameters = json_decode($message, true);
        if ($parameters === false || !isset($parameters['type']))
            return; // invalid JSON message.

        $type = $parameters['type'];
        if (isset($this->m_messageFilters[$type]))
            $message = $this->m_messageFilters[$type]($parameters);

        // TODO: Filter out foreign messages? Have a whitelist?

        $bots = array();
        foreach (BotManager::getInstance()->getBotList() as $bot)
            $bots[] = $bot;

        if (!count($bots))
            return;

        $bots[array_rand($bots)]->send('PRIVMSG ' . self::EchoChannel . ' :' . $message);
    }

    // Invoked each time a channel message has been received.
    public function onChannelPrivmsg(Bot $bot, $channel, $nickname, $message) {
        if (substr($message, 0, 1) != self::CommandPrefix)
            return;

        if (strtoupper($channel) != strtoupper(self::EchoChannel))
            return;

        $parameters = preg_split('/\s+/', $message);
        $command = substr(array_shift($parameters), 1);

        return $this->m_commands->processCommand($bot, $channel, $nickname, $command, $parameters);
    }

    // Invoked for each tick of the bots.
    public function onTick() {
        $this->m_serverPipe->poll($this);
    }

    // Reloads the message filters from the filters.php file.
    public function reloadMessageFilters() {
        include __DIR__ . '/filters.php';
        $this->m_messageFilters = $filters;
    }
};
