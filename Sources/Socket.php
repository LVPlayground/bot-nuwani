<?php
/**
 * Nuwani PHP IRC Bot Framework
 * Copyright (c) 2006-2010 The Nuwani Project
 *
 * Nuwani is a framework for IRC Bots built using PHP. Nuwani speeds up bot development by handling
 * basic tasks as connection- and bot management, timers and module managing. Features for your bot
 * can easily be added by creating your own modules, which will receive callbacks from the framework.
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
 *
 * @copyright Copyright (c) 2006-2011 The Nuwani Project, http://nuwani.googlecode.com/
 * @author Peter Beverloo <peter@lvp-media.com>
 * @author Dik Grapendaal <dik@sa-mp.nl>
 * @version $Id: Socket.php 137 2011-02-23 01:43:19Z dik.grapendaal $
 * @package Nuwani
 */

namespace Nuwani;
use \ SplQueue;

require_once __DIR__ . '/SocketStatistics.php';

/**
 * The Socket class will manage all communication with the IRC Server using PHP's stream socket
 * functions. Each bot which will be managed by Nuwani needs their own socket. Statistics for the
 * socket, such as the number of incoming and outgoing messages and bytes, will be stored within
 * the SocketStatistics class.
 * 
 * @package Nuwani
 */
class Socket
{
        /**
         * The send queue defines the maximum bot load at which we can directly distribute messages.
         * If the socket's load jumps above this value, a message will be queued until the load
         * drops low enough to be able to distribute the value accordingly.
         *
         * @todo This value has to be fine-tuned based on real-life testing on various IRC Servers.
         * @var integer
         */
        
        const   BUFFER_SEND_QUEUE               = 8000;
        
        /**
         * Set the timeout value for the socket. This timeout will be used when connecting to an IRC
         * server. Connection attempts are synchronous, so keep in mind that this value is the
         * maximum time the bot system could be blocked when connecting to the IRC server.
         *
         * @var float
         */
        
        const   SOCKET_TIMEOUT                  = 2.0;
        
        /**
         * The following five constants define the reasons which could have caused this socket to be
         * disconnected from the IRC Network. These are the values which are acceptable for the
         * onDisconnect callback which will be distributed to the modules.
         * 
         * @var integer
         */
        
        const   DISCONNECT_QUIT                 = 0;
        const   DISCONNECT_TIMEOUT              = 1;
        const   DISCONNECT_SOCKET_EOF           = 2;
        const   DISCONNECT_SOCKET_READ_ERR      = 3;
        const   DISCONNECT_SOCKET_WRITE_ERR     = 4;
        
        /**
         * The statistics for this socket will be stored within a separate SocketStatistics class,
         * an instance of which will be created within the constructor.
         *
         * @var SocketStatistics
         */
        
        private $statistics;
        
        /**
         * We maintain a distribution queue to ensure that the bot cannot exit the IRC Server due
         * to an Excess Flood error as thrown by the server. We imply a send queue which requires
         * the average load to be lower than a certain value.
         *
         * @var SplQueue
         */
        
        private $distributionQueue;
        
        /**
         * Settings for the connection will be stored in this property. More specifically, this 
         * array will have four entries: the server's IP and port, whether to use a secured SSL
         * connection and an IP address to bind our client socket to.
         *
         * @var array
         */
        
        private $connectionInfo;
        
        /**
         * We have to maintain a receive buffer because incoming messages are not per definition
         * complete. The last message in the incoming buffer will have to wait.
         *
         * @var string
         */
        
        private $receiveBuffer;
        
        /**
         * Communicating with an IRC server will be done through PHP's Stream functions, which allow
         * us to easily implement more advanced features such as SSL encryption and timeouts.
         * 
         * @var resource
         */
        
        private $socket;
        
        /**
         * In order to be able to distribute callbacks to the bot's core and modules, we have store
         * the actual bot we're serving the connection for.
         *
         * @var Bot
         */
        
        private $bot;
        
        /**
         * Initializing this class requires only the instance of the bot which we'll be managing the
         * socket for. The constructor will set up the SocketStatistics instance for this Socket, as
         * well as initializing the distribution queue.
         *
         * @param Bot $bot The bot which will own this socket.
         */
        
        public function __construct (Bot $bot)
        {
                $this -> statistics = new SocketStatistics ();
                $this -> distributionQueue  = new SplQueue ();
                
                $this -> receiveBuffer  = '';
                $this -> connectionInfo = array
                (
                        'bind_ip'       => null,
                        'server'        => null,
                        'port'          => 6667,
                        
                        'secure'        => false,
                        'blocking'      => false
                );
                
                $this -> bot = $bot;
        }
        
        /**
         * Setting the server information this socket should be using may be done by invoking this
         * method. Only the first parameter, the server's IP address or hostname, is required. The
         * other parameters allow you to set more advanced options to use.
         *
         * These options apply when the bot is connecting to the IRC Server. This means that you
         * can't change, for example, whether the socket should block without reconnecting.
         *
         * @param string $server Either the IP address or the hostname to use for this socket.
         * @param integer $port The port number to use to connect to the server.
         * @param boolean $secure Should this socket set up a secured connection?
         * @param boolean $blocking Should the socket be blocking? Highly discouraged.
         * @param mixed $bindIp The IP address to locally bind to, or null if there is none.
         * @return Socket The active socket instance, to allow call-chaining.
         */
        
        public function setServerInfo ($server, $port = 6667, $secure = false, $blocking = false, $bindIp = null)
        {
                $this -> connectionInfo = array
                (
                        'bind_ip'       => $bindIp,
                        'server'        => $server,
                        'port'          => $port,
                        
                        'secure'        => $secure,
                        'blocking'      => $blocking
                );
                
                return $this;
        }
        
        /**
         * Connecting to the IRC Server will combine all set options into a single Stream Context,
         * and, if possible, set up the connection itself. Nuwani stores a client certificate if
         * the socket should use a secured connection in the file "nuwani.pem".
         *
         * @return boolean Did we succeed in setting up the connection properly?
         */
        
        public function connect ()
        {
                $context = stream_context_create ();
                $scheme  = 'tcp';
                
                if (filter_var ($this -> connectionInfo ['bind_ip'], FILTER_VALIDATE_IP) !== false)
                {
                        stream_context_set_option ($context, 'socket', 'bindto', $this -> connectionInfo ['bind_ip']);
                }
                
                if ($this -> connectionInfo ['secure'] === true && extension_loaded ('openssl'))
                {
                        stream_context_set_option ($context, array
                        (
                                'ssl' => array
                                (
                                        'verify_peer'           => false,
                                        'allow_self_signed'     => true,
                                        'local_cert'            => __DIR__ . '/nuwani.pem',
                                        'passphrase'            => ''
                                )
                        ));
                        
                        $scheme = 'ssl';
                }
                
                $this -> socket = @ stream_socket_client
                (
                        $scheme . '://' . $this -> connectionInfo ['server'] . ':' . $this -> connectionInfo ['port'],
                        $errorNumber,
                        $errorString,
                        self :: SOCKET_TIMEOUT,
                        STREAM_CLIENT_CONNECT,
                        $context
                );
                
                if ($this -> socket !== false)
                {
                        if ($this -> connectionInfo ['blocking'] === false)
                        {
                                stream_set_blocking ($this -> socket, 0);
                        }
                        
                        return true;
                }
                
                $this -> bot -> onDisconnect (self :: DISCONNECT_TIMEOUT);
                $this -> disconnect ();
                
                return false;
        }
        
        /**
         * Properly closing the connection with the server can be done by invoking this method.
         * Distributing a callback to the bot and its modules has to be done separately.
         *
         * @todo This method should support termination reasons.
         */
        
        public function disconnect ()
        {
                if ($this -> socket !== null && $this -> socket !== false)
                {
                        stream_socket_shutdown ($this -> socket, STREAM_SHUT_RDWR);
                        fclose ($this -> socket);
                }
                
                $this -> socket = null;
                
                echo '[Socket] The connection to ' . $this -> connectionInfo ['server'] . ':' . $this -> connectionInfo ['port'] . ' has been terminated.' . PHP_EOL;
        }
        
        /**
         * Sending a command to the server may be done by invoking this method. Internally we'll
         * manage the outgoing buffer, statistics and bot load tracking, as well as actually sending
         * the message over the server like we're expected to.
         *
         * @param string $command The command which has to be send to the server.
         * @return integer The number of bytes which have been send at this very invocation.
         */
        
        public function send ($command)
        {
                if (is_resource ($this -> socket) === false)
                        return -1;
                
                $command = trim ($command);
                
                if ($this -> statistics -> load () >= self :: BUFFER_SEND_QUEUE)
                {
                        $this -> distributionQueue -> enqueue ($command);
                        return 0;
                }
                
                $this -> statistics -> push (SocketStatistics :: STATISTICS_OUTGOING, $command);
                
                if (($bytesWritten = fwrite ($this -> socket, $command . PHP_EOL)) === false)
                {
                        $this -> bot -> onDisconnect (self :: DISCONNECT_SOCKET_READ_ERR);
                        $this -> disconnect ();
                        
                        return -1;
                }
                
                if (strtoupper (substr ($command, 0, 4)) == 'QUIT')
                {
                        $this -> bot -> onDisconnect (self :: DISCONNECT_QUIT);
                        $this -> disconnect ();
                        
                        BotManager :: getInstance () -> destroy ($this -> bot);
                }
                
                return $bytesWritten;
        }
        
        /**
         * Processing updates has to be done every frame of the bot. In this method we'll handle
         * incoming messages, statistics for them and, if they're available, distributing messages
         * in the distribution queue. 
         */
        
        public function update ()
        {
                if (is_resource ($this -> socket) === false)
                        return;
                
                $this -> statistics -> update ();
                
                if (count ($this -> distributionQueue) > 0)
                {
                        if ($this -> statistics -> load () < self :: BUFFER_SEND_QUEUE)
                        {
                                $this -> send ($this -> distributionQueue -> dequeue ());
                        }
                }
                
                if (feof ($this -> socket))
                {
                        $this -> bot -> onDisconnect (self :: DISCONNECT_SOCKET_EOF);
                        $this -> disconnect ();
                        
                        return;
                }
                
                $incoming = fread ($this -> socket, 2048);
                if ($incoming === false)
                {
                        $this -> bot -> onDisconnect (self :: DISCONNECT_SOCKET_READ_ERR);
                        $this -> disconnect ();
                        
                        return;
                }
                
                $messageQueue = explode ("\n", ltrim ($this -> receiveBuffer . $incoming));
                $this -> receiveBuffer = array_pop ($messageQueue);
                
                foreach ($messageQueue as $command)
                {
                        $command = trim ($command);
                        
                        $this -> statistics -> push (SocketStatistics :: STATISTICS_INCOMING, $command);
                        $this -> bot -> onReceive ($command);
                }
                
                return true;
        }
        
        /**
         * Gaining access to the incoming and outgoing statistics for this socket may be done by
         * invoking this method. Different from the statistic class itself, we'll return both the
         * incoming and outgoing statistics.
         *
         * @return array All known statistics, with the exception of the bot's load.
         */
        
        public function statistics ()
        {
                return array
                (
                        'incoming'      => $this -> statistics -> statistics (SocketStatistics :: STATISTICS_INCOMING),
                        'outgoing'      => $this -> statistics -> statistics (SocketStatistics :: STATISTICS_OUTGOING)
                );
        }
        
        /**
         * Getting the socket load can be convenient for a number of things, not the least important
         * of which is the ability to load balance outgoing messages. The BotGroup class will have
         * the ability to do this for you, in a specific channel.
         *
         * @param integer $direction Do you want to retrieve the incoming or outgoing load?
         * @return float The average load measured over a number of seconds.
         */
        
        public function load ($direction = SocketStatistics :: STATISTICS_OUTGOING)
        {
                return $this -> statistics -> load ($direction);
        }
}
