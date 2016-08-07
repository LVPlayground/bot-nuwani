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
 * @package Connection Module
 * @author Peter Beverloo <peter@lvp-media.com>
 * @author Dik Grapendaal <dik@sa-mp.nl>
 * @see http://nuwani.googlecode.com
 */

use Nuwani\Bot;
use Nuwani\ModuleManager;
use Nuwani\Timer;

class Connection extends ModuleBase
{
        /**
         * This function will be called as soon as a bot has connected to a
         * network. We'll be executing automated commands- and joins here.
         *
         * @param Bot $pBot The bot who is connecting with a server.
         */

        public function onConnect (Bot $pBot)
        {
                if (isset ($pBot ['OnConnect']))
                {
                        $aChannels    = array ();
                        $aConnectInfo = $pBot ['OnConnect'];

                        foreach ($aConnectInfo as $sCommand)
                        {
                                if (!is_array($sCommand)) {
                                        $pBot -> send ($sCommand);
                                }
                        }
                }
        }

        /**
         * Something unknown, weird, unexplored or otherwise not included has been
         * received by this bot, and this is a function in which we'll handle it. This
         * function will act as a connection-helper, if anything fails, we'll make
         * sure the bot can connect either way.
         *
         * @param Bot $pBot Bot which received this command.
         */

        public function onUnhandledCommand (Bot $pBot)
        {
                switch ($pBot -> In -> Chunks [1])
                {
                        /** Sonium :Nickname is already in use. **/
                        case 433:
                        {
                                switch ($pBot -> In -> Chunks [3])
                                {
                                        case $pBot ['Nickname']:
                                        {
                                                /** Try alternative nickname, if set up, else fall through. **/
                                                if (isset ($pBot ['AltNickname']))
                                                {
                                                        $sNickname = $pBot ['AltNickname'];
                                                        break ;
                                                }
                                        }

                                        default:
                                        {
                                                $sNickname = $pBot ['Nickname'] . '_' .  rand (1000,9999);
                                                break ;
                                        }
                                }

                                $pBot -> send ('NICK ' . $sNickname);

                                break;
                        }

                        /** USER :Not enough parameters **/
                        case 461:
                        {
                                $sUsername = !empty ($pBot ['Username']) ? $pBot ['Username'] : NUWANI_NAME;
                                $sRealname = !empty ($pBot ['Realname']) ? $pBot ['Realname'] : NUWANI_VERSION_STR;
                                $pBot -> send ('USER ' . $sUsername . ' - - :' . $sRealname);

                                break;
                        }

                        /** Killed by the server for whatever reason. **/
                        case 'KILL':
                        {
                                Timer :: Create (function () use ($pBot)
                                {
                                        $pBot ['Socket'] -> close ();
                                        $pBot -> connect ();

                                }, 1000, false);

                                break;
                        }
                }
        }

        /**
         * This function gets invoked when the bot is about to shut down, so right
         * before unregistering itself and sending the IRC quit messages.
         *
         * @param Bot $pBot The bot who will be disconnecting in a sec.
         */

        public function onShutdown (Bot $pBot)
        {
                if (isset ($pBot ['QuitMessage']))
                {
                        $pBot -> send ('QUIT :' . $pBot ['QuitMessage']);
                        return ModuleManager :: FINISHED;
                }
        }

}
