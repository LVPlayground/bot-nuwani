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
 * @package Invite Module
 * @author Peter Beverloo <peter@lvp-media.com>
 * @author Dik Grapendaal <dik@sa-mp.nl>
 * @see http://nuwani.googlecode.com
 */

use Nuwani \ ModuleManager;

class Invite extends ModuleBase
{
        /**
         * This function gets invoked when someone invites a person or a bot in to
         * a channel. This could be us, and after all, it'd be fancy if the bot
         * owner would be capable of inviting his/her bots in various channels.
         * 
         * @param Bot $pBot The bot who received the invite message.
         * @param string $sInviter Nickname of the person who invites someone.
         * @param string $sInvitee Nickname of the person being invited.
         * @param string $sChannel Channel in which the invitation occurs.
         */
        
        public function onInvite (Bot $pBot, $sInviter, $sInvitee, $sChannel)
        {
                if ($pBot ['Nickname'] != $sInvitee)
                {
                        return false ;
                }
                
                $pEvaluationModule = ModuleManager :: getInstance () -> offsetGet ('Evaluation');
                if ($pEvaluationModule !== false)
                {
                        if (!$pEvaluationModule -> checkSecurity ($pBot, ISecurityProvider :: BOT_OWNER))
                        {
                                return false ;
                        }
                        
                        $pBot -> send ('JOIN ' . $sChannel);
                        return true ;
                }
                
                return false ;
        }

};

?>
