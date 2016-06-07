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
 * @version $Id: MessageFactory.php 136 2011-02-23 01:23:09Z dik.grapendaal $
 * @package Nuwani
 */
 
namespace Nuwani;

require_once __DIR__ . '/Message.php';

require_once __DIR__ . '/Messages/UnknownMessage.php';
require_once __DIR__ . '/Messages/UserMessage.php';

require_once __DIR__ . '/Messages/NoticeMessage.php';

/**
 * To centralize the logic required to create new instances of the Message
 * classes, this base-class named MessageFactory is available. The two
 * primary components it contains are the create method, which actually is
 * a factory method allowing you to create new objects.
 *
 * @package Nuwani
 */
abstract class MessageFactory
{
        /**
         * All the types of incoming messages we can expect from the IRC
         * server(s) have to be listed here. Please make sure that textual
         * representation of the type has to be available in the create()
         * method in order for it to work correctly.
         * 
         * Except for the truly textual types, all values of these contants are
         * based on the numerics used by common IRC Servers. The full list may
         * be found on the following URL: http://www.mirc.net/raws/
         *
         * @var integer
         */
        
        const   MESSAGE_TYPE_UNKNOWN            = 0;
        const   MESSAGE_TYPE_NOTICE             = 1;
        
        /**
         * All the known message types have to be listed in this array for the
         * factory method to work properly. We'll be iterating over the first
         * word of the $messageType if it's a string, or we'll just be fetching
         * the class-name if it's an integer.
         * 
         * @see MessageFactory::create
         * @var array
         */
        
        private static $messageTypeMap = array
        (
                self :: MESSAGE_TYPE_UNKNOWN    => array ('UnknownMessage',     null),
                self :: MESSAGE_TYPE_NOTICE     => array ('NoticeMessage',      'notice')
        );
        
        /**
         * Creating a new message is a job for this class. For additional
         * convenience we won't be too strict on the type of the $messageType
         * variable, which can either be a string or an integer. If the type
         * contains spaces, we'll assume there is information which has to be
         * parsed by the message's class and pass it along.
         *
         * An instance of an UnknownMessage class will be returned if we cannot
         * make sense of the incoming message. This should never occur.
         *
         * @param mixed $messageType Information about the message we'll handle.
         * @return Message A class specific to the given message's type.
         */
        
        public static final function create ($messageType)
        {
                $messageClassName = null;
                $commandText      = false;
                
                if (ctype_digit ($messageType))
                {
                        if (isset (self :: $messageTypeMap [$messageType]))
                        {
                                $messageClassName = self :: $messageTypeMap [$messageType] [0];
                        }
                }
                else if (is_string ($messageType))
                {
                        $commandText = $messageType;
                        $commandName = strtolower ($messageType);
                        
                        if (substr ($commandName, 0, 1) == ':')
                        {
                                $commandName = ltrim (strstr ($commandName, ' '));
                        }
                        
                        if (strpos ($commandName, ' ') !== false)
                        {
                                $commandName = strstr ($commandName, ' ', true);
                        }
                        
                        foreach (self :: $messageTypeMap as $messageId => $messageInfo)
                        {
                                if ($messageInfo [1] === $commandName)
                                {
                                        $messageClassName = $messageInfo [0];
                                        break;
                                }
                        }
                }
                
                if ($messageClassName === null || !class_exists ($messageClassName, false))
                {
                        $messageClassName = self :: $messageTypeMap [self :: MESSAGE_TYPE_UNKNOWN] [0];
                }
                
                $instance = new $messageClassName ();
                if ($commandText !== false)
                {
                        $instance -> parse ($commandText);
                }
                
                return $instance;
        }
};
