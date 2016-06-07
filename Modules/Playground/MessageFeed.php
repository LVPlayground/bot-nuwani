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

class MessageFeed {
    private $m_channel;
    private $m_eventListener;
    private $m_socket;
    private $m_debug;

    public function __construct($bindAddress, $bindPort, $channel, $eventListener) {
        $this->m_channel = $channel;
        $this->m_eventListener = $eventListener;
        
        $this->m_socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_bind($this->m_socket, $bindAddress, $bindPort);
        socket_set_nonblock($this->m_socket);

        $this->m_debug = false;
    }

    public function setDebug($value) {
        $this->m_debug = $value;
    }
    
    public function poll($callback) {
        if (@socket_recvfrom($this->m_socket, $buffer, 65536, 0, $client, $port) === false || strlen($buffer) == 0)
            return;
        
        $lines = preg_split('/\n/', $buffer, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            $callback($trimmedLine);

            if ($this->m_debug) {
                file_put_contents('Data/MessageFeed_' . $this->m_channel . '.log', date('[r] ') . $trimmedLine . PHP_EOL, FILE_APPEND);
            }
        }
    }
    
    public function channel() {
        return $this->m_channel;
    }

    public function eventListener() {
        return $this->m_eventListener;
    }
};
