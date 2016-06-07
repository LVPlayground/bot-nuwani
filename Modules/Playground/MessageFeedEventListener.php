<?php
/**
 * Copyright (c) 2006-2013 Las Venturas Playground
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/>.
 */

namespace Playground;

use \ DirectoryIterator;
use \ Nuwani \ BotManager;

// This class is intended to implement special behavior for certain kinds of incoming events.
class MessageFeedEventListener {
    private $m_configuration;
    
    public function __construct($configuration = array()) {
        $this->m_configuration = $configuration;
    }

    public function onGamemodeInitialization($matches) {
        $channels = TargetChannel::getChannels($this->m_configuration['announcement_channels']);
        if (!is_array($channels)) {
            $channels = array($channels);
        }
        foreach ($channels as $channel) {
            $bot = BotManager::getInstance()->offsetGet('channel:' . $channel);
            if ($bot === false)
                continue;
            
            if ($bot instanceof \ Nuwani \ BotGroup)
                $bot = $bot->current();
            
            $bot->send('PRIVMSG ' . $channel . ' :4*** Global Gamemode Initialization');
        }
        
        // When a server crash occurs, we'd like to check the logs directory for any file
        // that has been touched in the last five minutes, dig up a crash stack from there
        // and share it in the #LVP.dev channel if we can find one.
        if (is_dir($this->m_configuration['logs_directory']) === false)
            return;
        
        $latestFile = null;
        $latestFileTime = 0;
        
        foreach (new DirectoryIterator($this->m_configuration['logs_directory']) as $file) {
            if ($file->isDir())
                continue;
            
            if ($file->getMTime() > $latestFileTime) {
                $latestFile = $file;
                $latestFileTime = $file->getMTime();
            }
        }

        if ($latestFile === null || (time() - $latestFileTime) > 5 * 60)
            return;

        $output = shell_exec('/usr/bin/tail -n 25 "' . $file->getFilename() . '" | grep "\\[debug\\]"');
        $lines = preg_split('/\n/s', $output, 0, PREG_SPLIT_NO_EMPTY);
        
        if (count($lines) > 12)
            continue;
        
        $bot = BotManager::getInstance()->offsetGet('channel:' . TargetChannel::developmentChannel());
        if ($bot === false)
            continue;
        
        if ($bot instanceof \ Nuwani \ BotGroup)
            $bot = $bot->current();
        
        foreach($lines as $line)
            $bot->send('PRIVMSG ' . TargetChannel::developmentChannel() . ' :5' . trim($line));

        // Let's ask the gamemode for all the available commands which are already available in the game-
        // mode and we should process.
        \ Playground::sendIngameCommand('requestcommands');
    }

    public function __call($method, $arguments) {
        echo '[MessageFeedEventListener] Unknown event triggered: ' . $method . PHP_EOL;
    }
};
