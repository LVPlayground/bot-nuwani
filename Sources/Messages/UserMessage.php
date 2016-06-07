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
 * @version $Id: UserMessage.php 136 2011-02-23 01:23:09Z dik.grapendaal $
 * @package Nuwani
 */
 
namespace Nuwani;

/**
 * This class is available for each message which has to be prepended by an User
 * string, containing a user's nickname, ident and hostname. Since servers can
 * be sending us messages as well, parsing has to be careful and complete.
 *
 * @package Nuwani
 */
abstract class UserMessage extends Message
{
        /**
         * Information about the user, machine or server sending this message
         * will be contained within this property. It'll be an instance of the
         * UserInfo class, available in the Sources directory.
         *
         * @var UserInfo
         */
        
        public $user;
        
        /**
         * Extracting the user infor is a job solely for the parse function.
         * Next to that we 
         *
         * @param string $message The message from which extract the user info.
         * @return boolean Were we able to properly parse the message?
         */
        
        public function parse ($message)
        {
                /**
                 * @todo Implement this
                 */
        }
};
