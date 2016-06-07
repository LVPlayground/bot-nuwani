<?php
// Lost Venturas Playground <http://jcmp.nl/>
// Copyright (c) 2014 The Lost Venturas Playground authors. All rights reserved.
//
// Licensed under the MIT license, a copy of which is available in the LICENSE file.

namespace JustCause;

interface MessagePipeListener {
    // Invoked when a message has been received over the Message Pipe. The |$address| and |$port|
    // arguments indicate where the message came from.
    public function onMessageReceived($message, $address, $port);
};

class MessagePipe {
    private $m_distributionSocket;

    private $m_commandPort;
    private $m_commandAddress;

    public function __construct($commandPort, $commandAddress, $distributionPort) {
        $this->m_commandPort = $commandPort;
        $this->m_commandAddress = $commandAddress;

        $this->m_commandSocket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_nonblock($this->m_commandSocket);

        $this->m_distributionSocket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_nonblock($this->m_distributionSocket);
        socket_bind($this->m_distributionSocket, '0.0.0.0', $distributionPort);
    }

    public function sendCommand($message) {
        $message = trim($message) . PHP_EOL;

        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_sendto($socket, $message, strlen($message), 0, '127.0.0.1', 27122);
        socket_close($socket);
    }

    public function poll(MessagePipeListener $listener) {
        $status = socket_recvfrom($this->m_distributionSocket, $buffer, 1024, 0, $address, $port);
        if ($status === false || $status === 0)
            return false;

        $messages = explode(PHP_EOL, trim($buffer));
        foreach ($messages as $message)
            $listener->onMessageReceived(trim($message), $address, $port);
    }
};
