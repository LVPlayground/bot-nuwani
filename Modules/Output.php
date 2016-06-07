<?php
/**
 * Nuwani PHP IRC Bot Framework
 * Copyright (c) 2006-2010 The Nuwani Project
 *
 * Nuwani is a framework for IRC Bots built using PHP. Nuwani speeds up bot 
 * development by handling basic tasks as connection- and bot management, timers
 * and module managing. Features for your bot can easily be added by creating
 * your own modules, which will receive callbacks from the framework.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright (c) 2006-2010 The Nuwani Project
 * @package Output Module
 * @author Peter Beverloo <peter@lvp-media.com>
 * @author Dik Grapendaal <dik@sa-mp.nl>
 * @see http://nuwani.googlecode.com
 */

class Output extends ModuleBase
{
        /**
         * This function will receive the private messages received by us which
         * did not occur in a channel, which could be an upset estroe.
         * 
         * @param Bot $pBot The bot which received this message. 
         * @param string $sNickname Nickname who is PM'ing us. 
         * @param string $sMessage The message being send to us. 
         */
         
        public function onPrivmsg (Bot $pBot, $sNickname, $sMessage)
        {
                echo '[' .$sNickname . '] private: ' . $sMessage . PHP_EOL;
        }
        
        /**
         * An error could occur for various reasons. Not-initialised variables, deviding
         * things by zero, or using older PHP functions which shouldn't be used.
         * 
         * @param integer $nErrorType Type of error that has occured, like a warning. 
         * @param string $sErrorString A textual representation of the error 
         * @param string $sErrorFile File in which the error occured 
         * @param integer $nErrorLine On which line did the error occur? 
         */
        
        public function onError (Nuwani \ Bot $pBot, $nErrorType, $sErrorString, $sErrorFile, $nErrorLine)
        {
                switch ($nErrorType)
                {
                        case E_WARNING:
                        case E_USER_WARNING:    { echo '[Warning]';       break; }
                        case E_NOTICE:
                        case E_USER_NOTICE:     { echo '[Notice]';        break; }
                        case E_DEPRECATED:
                        case E_USER_DEPRECATED: { echo '[Deprecated]';    break; }
                }
                
                echo ' Error occured in "' . $sErrorFile . '" on line ' . $nErrorLine . ': "';
                echo $sErrorString . '".' . PHP_EOL;
        }
        
        /**
         * When exceptions occur, it would be quite convenient to be able and fix them
         * up. That's why this function exists - output stuff about the exception.
         * 
         * @param Bot $pBot The bot that was active while the exception occured. 
         * @param string $sSource Source of the place where the exception began. 
         * @param Exception $pException The exception that has occured. 
         */
        
        public function onException (Bot $pBot, $sSource, Exception $pException)
        {
                $sMessage  = '[Exception] Exception occured in "' . $pException -> getFile () . '" on line ';
                $sMessage .= $pException -> getLine () . ': "' . $pException -> getMessage () . '".' . PHP_EOL;
                
                if ($sSource !== null && $pBot instanceof Bot)
                {
                        $pBot -> send ('PRIVMSG ' . $sSource . ' :' . $sMessage);
                }
                
                echo $sMessage;
        }
};

?>
