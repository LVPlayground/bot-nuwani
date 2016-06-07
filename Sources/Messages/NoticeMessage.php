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
 * @version $Id: NoticeMessage.php 136 2011-02-23 01:23:09Z dik.grapendaal $
 * @package Nuwani
 */
 
namespace Nuwani;

/**
 * NOTICE messages are different from PRIVMSG messages because neither servers
 * nor clients are supposed to give automated responses to received messages.
 * These days they're more commonly used for in-line private messages, and
 * sometimes as an annoyance towards other uses as some clients create BEEPS
 * upon receiving NOTICE messages.
 *
 * @see Section 4.4.2. of RFC 1459 <http://tools.ietf.org/html/rfc1459>
 * @package Nuwani
 */
class NoticeMessage extends UserMessage
{
        /**
         * The only functionality that has to be added in order to properly
         * support notices, it a container for its message. The user part will
         * be handled by the UserMessage class, which we inherit from.
         *
         * @var string
         */
        
        public $message;
        
        /**
         * This is the base constructor. When creating a class which inherits
         * Message, please be sure that this method is called, as other
         * functionality might be added to it later on.
         * 
         * @param integer $messageType Type of the message we're dealing with.
         */
        
        protected function __construct ()
        {
                parent :: __construct (self :: MESSAGE_TYPE_NOTICE);
        }
        
        /**
         * Most message-types got textual representations as well, which may be
         * retrieved by invoking this method. Each message type has to implement
         * this seperately, as it's part of their individual specifics.
         *
         * @return string Textual representation of the current type.
         */
        
        public function typeName ()
        {
                return 'notice';
        }
        
        /**
         * Parsing a message will mainly be convenient to automatically translate
         * the incoming message to a format this class can understand.
         *
         * @param string $message The message which should be parsed.
         * @return boolean Were we able to properly parse the message?
         */
        
        public function parse ($message)
        {
                parent :: parse ($message);
                
                /**
                 * @todo Implement this
                 */
        }
        
        /**
         * Casting a type to a string may be done to retrieve a full, compatible
         * command which is ready to be send to an IRC Server. 
         * 
         * @return string The full command to distribute to an IRC server.
         */
        
        public function __toString ()
        {
                return 'NOTICE ' . $this -> user . ': ' . $this -> message;
        }
        
};
