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
 * @package CTCP Module
 * @author Peter Beverloo <peter@lvp-media.com>
 * @author Dik Grapendaal <dik@sa-mp.nl>
 * @see http://nuwani.googlecode.com
 */

use Nuwani\Bot;

class CTCP extends ModuleBase
{
        /**
         * To make the bot more interactive with the IRC server, as well as with
         * other clients, we want a default set of replies to CTCP requests.
         *
         * @param Bot $pBot The bot who received this CTCP-message.
         * @param string $sSource Source channel or nickname of the message.
         * @param string $sType Type of message that has been received.
         * @param string $sMessage The actual received message.
         */

        public function onCTCP (Bot $pBot, $sSource, $sNickname, $sType, $sMessage)
        {
                switch (trim ($sType))
                {
                        case 'VERSION':
                        {
                                $this -> sendCTCP ($pBot, $sNickname, 'VERSION', '"' . $pBot ['Nickname'] . '" running ' . NUWANI_VERSION_STR . (extension_loaded('runkit') ? ' and using Runkit!' : ''));
                                break;
                        }

                        case 'PING':
                        {
                                $this -> sendCTCP ($pBot, $sNickname, 'PING', trim ($sMessage));
                                break;
                        }

                        case 'TIME':
                        {
                                if (strlen ($sMessage))
                                {
                                        /** reply from another client **/
                                        break ;
                                }

                                $this -> sendCTCP ($pBot, $sNickname, 'TIME', date('D M d H:i:s Y'));
                                break;
                        }

                        case 'URL':
                        case 'FINGER':
                        {
                                $this -> sendCTCP ($pBot, $sNickname, $sType, 'Check out http://nuwani.googlecode.com!');
                                break;
                        }
                }
        }

        /**
         * A small helper function to distribute the CTCP reply to the IRC
         * Network, mainly because it needs all kinds of characters.
         *
         * @param Bot $pBot The bot that will be sending this message.
         * @param string $sNickname Nickname to reply to, e.g. the destination.
         * @param string $sType Type of CTCP message that will be send.
         * @param string $sMessage The message associated with the CTCP.
         */

        private function sendCTCP (Bot $pBot, $sNickname, $sType, $sMessage)
        {
                $sCommand = 'NOTICE ' . $sNickname . ' :' . self :: CTCP . $sType;
                if (strlen ($sMessage) > 0)
                {
                        $sCommand .= ' ' . $sMessage;
                }

                $pBot -> send ($sCommand . self :: CTCP);
        }
}
