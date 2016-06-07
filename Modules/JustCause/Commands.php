<?php
// Lost Venturas Playground <http://jcmp.nl/>
// Copyright (c) 2014 The Lost Venturas Playground authors. All rights reserved.
//
// Licensed under the MIT license, a copy of which is available in the LICENSE file.

namespace JustCause;

use \ Bot;

class Commands {
    // Message pipe using which we can communicate with the server.
    private $m_serverPipe;

    private $m_module;

    public function __construct(MessagePipe $serverPipe, $module) {
        $this->m_serverPipe = $serverPipe;
        $this->m_module = $module;
    }

    public function processCommand(Bot $bot, $channel, $nickname, $command, $parameters) {
        switch ($command) {
            case 'msg':
                $this->onMessageCommand($nickname, $parameters);
                return true;
            case 'players':
                $this->onPlayersCommand();
                return true;
            case 'reloadfilters':
                $this->m_module->reloadMessageFilters();
                return true;
        }

        return false;
    }

    public function onMessageCommand($nickname, $parameters) {
        $this->m_serverPipe->sendCommand('msg ' . $nickname . ' ' . implode(' ', $parameters));
    }

    public function onPlayersCommand() {
        $this->m_serverPipe->sendCommand('players');
        echo 'Sending "players" command..' . PHP_EOL;
    }
};
