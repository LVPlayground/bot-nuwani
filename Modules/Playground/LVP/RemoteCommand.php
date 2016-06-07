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
use \ LVP;

const SocketTimeoutSeconds = 2;
const ReceiveBufferBytes = 4096;

class RemoteCommand {
    private static $m_hostname;
    private static $m_port;
    private static $m_password;
    
    private static $m_socket;

    private static $m_receiveBuffer;
    private static $m_receiveBufferLength;
    private static $m_receiveBufferIndex;
    
    public static function setRemoteCommandInformation($hostname, $port, $password) {
        self::$m_hostname = GetHostByName($hostname);
        self::$m_port = (int) $port;
        self::$m_password = $password;
        
        self::$m_socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option(self::$m_socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => SocketTimeoutSeconds, 'usec' => 0));
        socket_set_option(self::$m_socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => SocketTimeoutSeconds, 'usec' => 0));
    }
    
    public static function sendRemoteCommand($command, $expectReturnText) {
        $package = 'SAMP';
        foreach (explode('.', self::$m_hostname) as $decimal)
            $package .= chr($decimal);
        
        $package .= chr(self::$m_port & 0xFF);
        $package .= chr(self::$m_port >> 8 & 0xFF);
        $package .= 'x';

        $package .= chr(strlen(self::$m_password) & 0xFF);
        $package .= chr(strlen(self::$m_password) >> 8 & 0xFF);
        $package .= self::$m_password;
        $package .= chr(strlen($command) & 0xFF);
        $package .= chr(strlen($command) >> 8 & 0xFF);
        $package .= $command;
        
        if (socket_sendto(self::$m_socket, $package, strlen($package), 0, self::$m_hostname, self::$m_port) === false)
            return false;
        
        if ($expectReturnText === false)
            return true;
        
        $resultLines = array();
        while (self::receiveIncomingData(ReceiveBufferBytes) !== false) {
            if (self::read(4) != 'SAMP')
                break;
            
            self::read(9); // disregard the next nine bytes received.
            $receivedLine = self::read(self::$m_receiveBufferLength - 13);
            if (strlen($receivedLine) == 0)
                break;

            $resultLines[] = $receivedLine;
        }

        return $resultLines;
    }
    
    public static function getServerInformation($informationIdentifier) {
        $package = 'SAMP';
        foreach (explode('.', self::$m_hostname) as $decimal)
            $package .= chr($decimal);
        
        $package .= chr(self::$m_port & 0xFF);
        $package .= chr(self::$m_port >> 8 & 0xFF);
        $package .= $informationIdentifier;
        
        if (socket_sendto(self::$m_socket, $package, strlen($package), 0, self::$m_hostname, self::$m_port) === false)
            return false;
        
        if (self::receiveIncomingData(ReceiveBufferBytes) === false)
            return false;
        
        switch($informationIdentifier) {
            case LVP::GeneralInformation:
                if (self::read(4) != 'SAMP')
                    return false;
                
                self::read(7); // disregard the next seven bytes received.
                return array(
                    'password' => self::readInteger(1) == 1,
                    'players' => self::readInteger(2),
                    'max_players' => self::readInteger(2),
                    'hostname' => self::readString(4),
                    'gamemode' => self::readString(4),
                    'map' => self::readString(4)
                );

            case LVP::ServerRuleInformation:
                if (self::read(4) != 'SAMP')
                    return false;
                
                self::read(7); // disregard the next seven bytes received.
                $ruleCount = self::readInteger(2);
                $rules = array();
                
                for ($ruleIndex = 0; $ruleIndex < $ruleCount; ++$ruleIndex) {
                    $ruleName = self::readString(1);
                    $ruleValue = self::readString(1);
                    
                    $rules[$ruleName] = $ruleValue;
                }
                
                return $rules;
                
            case LVP::PlayerInformation:
                if (self::read(4) != 'SAMP')
                    return false;
                
                self::read(7); // disregard the next seven bytes received.
                $playerCount = self::readInteger(2);
                $players = array();
                
                for ($playerIndex = 0; $playerIndex < $playerCount; ++$playerIndex) {
                    $players[self::readInteger(1)] = array(
                        'nickname' => self::readString(1),
                        'score' => self::readNumber(),
                        'ping' => self::readInteger(4)
                    );
                }
            
                return $players;
        }
        
        return false;
    }
    
    private static function receiveIncomingData($length) {
        self::$m_receiveBufferLength = socket_recvfrom(self::$m_socket, self::$m_receiveBuffer, $length, 0, self::$m_hostname, self::$m_port);
        self::$m_receiveBufferIndex = 0;
        
        return self::$m_receiveBufferLength !== false;
    }
    
    private static function read($length) {
        if ((self::$m_receiveBufferIndex + $length) > self::$m_receiveBufferLength)
            return '';
        
        $bytesRead = 0;
        $buffer = '';
        
        while ($bytesRead++ < $length)
            $buffer .= self::$m_receiveBuffer[self::$m_receiveBufferIndex++];
        
        return $buffer;
    }
    
    private static function readInteger($length) {
        return ord(self::read($length));
    }
    
    private static function readNumber() {
        $value = self::read(4);
        if (strlen($value) != 4)
            return 0;
        
        $number = (ord($value[0])) +
                  (ord($value[1]) << 8) +
                  (ord($value[2]) << 16) +
                  (ord($value[3]) << 24);
        
        if ($number >= 4294967294)
            $number -= 4294967296;
        
        return $number;
    }
    
    private static function readString($lengthSize) {
        $response = self::read($lengthSize);
        if (strlen($response) == 0 || ord($response) <= 0)
            return '';
        
        return self::read(ord($response));
    }
};
