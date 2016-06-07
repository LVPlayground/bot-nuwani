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
 * @version $Id: BotManager.php 136 2011-02-23 01:23:09Z dik.grapendaal $
 * @package Nuwani
 */
 
namespace Nuwani;

class BotManager extends Singleton implements \ ArrayAccess, \ Countable
{
        /**
         * Contains a list with all the bots loaded into the system. All bots
         * are listed using their instance; other data is listed within.
         * 
         * @var array
         */
        
        private $m_aBotList;
        
        /**
         * In order to properly catch- and process shell signals coming from the
         * Linux shell, we have to know whether the functions are avaialble.
         * 
         * @var boolean
         */
        
        private $m_bDispatchSignals;
        
        /**
         * The centralized function which will properly initialise all initial
         * bots to be initialised into the Nuwani Bot system.
         * 
         * @param array $aBotList The bots that have to be initialised
         */
        
        public function Initialize ($aBotList)
        {
                foreach ($aBotList as $aBotInfo)
                {
                        $pBot = new Bot ($aBotInfo ['Nickname']);
                        $pBot -> setNetwork ($aBotInfo ['Network']);
                        
                        $network = $pBot -> network ();
                        $bindIp  = isset ($aBotInfo ['BindIP']) ? $aBotInfo ['BindIP'] : null;
                        
                        // Old behavior.
                        $network ['Port'] = $aBotInfo ['SSL'] !== false ? $aBotInfo ['SSL'] : $network ['Port'];
                        
                        $pBot ['Socket'] -> setServerInfo ($network ['Address'], $network ['Port'], $aBotInfo ['SSL'] !== false, false, $bindIp);
                        
                        $pBot -> setBotInfo ($aBotInfo); 
                        $pBot -> connect ();
                }
                
                if (function_exists ('pcntl_signal_dispatch'))
                {
                        pcntl_signal (SIGTERM,  array ($this, 'onCatchSignal'));
                        pcntl_signal (SIGINT,   array ($this, 'onCatchSignal'));
                        pcntl_signal (SIGTERM,  array ($this, 'onCatchSignal'));
                        
                        $this -> m_bDispatchSignals = true;
                }
                else
                {
                        $this -> m_bDispatchSignals = false;
                }
        }
        
        /**
         * The destructor will properly shut down all bots running under the
         * Nuwani platform, in the order where they got started.
         */
        
        public function __destruct ()
        {
                foreach ($this -> m_aBotList as $nBotIndex => $aBotInfo)
                {
                        $this -> unregister ($aBotInfo ['Instance']);
                }
        }
        
        /**
         * This function returns an array with all bot instances in the system,
         * as a BotGroup. That way no dirty hacks have to be implemented in
         * offsetGet().
         * 
         * @return BotGroup
         */
        
        public function getBotList ()
        {
                $aBotList = array ();
                foreach ($this -> m_aBotList as $aBotInfo)
                {
                        if ($aBotInfo ['Instance'] instanceof Bot)
                        {
                                $aBotList [] = $aBotInfo ['Instance'];
                        }
                }
                
                return new BotGroup ($aBotList);
        }

        /**
         * The register function will add a new bot to the internal lists,
         * allowing it to be used by other systems (like IRC Echo for games).
         * 
         * @param Bot $pBot The bot that is going to be registered.
         * @param string $sNickname A reference to the bot's current nickname.
         */
        
        public function register (Bot $pBot, & $sNickname)
        {
                $this -> m_aBotList [] = array
                (
                        'Instance'      => $pBot,
                        'Started'       => time (),
                        'Nickname'      => $sNickname
                );
        }
        
        /**
         * After a bot shuts down, we have to be informed and remove it from the
         * bot tracking array. It will no longer be used for whatever service
         * possible.
         * 
         * @param Bot $pBot The bot that is going to be unregistered.
         * @return boolean
         */
        
        public function unregister (Bot $pBot)
        {
                foreach ($this -> m_aBotList as $nIndex => $pListedBot)
                {
                        if ($pListedBot ['Instance'] != $pBot)
                        {
                                /** This is another bot. **/
                                continue ;
                        }
                        
                        $pBot -> destroy ();
                        
                        unset ($pListedBot, $pBot); // silly php refcount
                        unset ($this -> m_aBotList [$nIndex]);
                        
                        return true;
                }
                
                return false;
        }
        
        /**
         * This function creates a bot with the specified name and network, and 
         * optionally joins a number of channels.
         * 
         * @param string $sName Nickname of the bot to create.
         * @param string $sNetwork Name of the network to connect with.
         * @param array $aChannels Array of channels to auto-join.
         */
        
        public function create ($sName, $sNetwork, $aChannels = array ())
        {
                $pBot = new Bot ($sName);
                $pBot -> setNetwork ($sNetwork);
                $pBot -> connect ();
                
                foreach ((array) $aChannels as $sChannel)
                {
                        $pBot -> send ('JOIN ' . $sChannel);
                }
        }
        
        /**
         * This function is an alias for unregister, seeing create- and destroy
         * logically come together, and not create- and unregister. That'd be odd.
         * 
         * @param Bot $pBot The bot that you want to destroy.
         */
        
        public function destroy (Bot $pBot)
        {
                $this -> unregister ($pBot);
        }
        
        /**
         * The process function runs everythign for all bots, and because we are
         * the only place where everything is known, we have to do so!
         */
        
        public function process ()
        {
                foreach ($this -> m_aBotList as $iBotIndex => $pBot)
                {
                        $pBot ['Instance'] -> process ();
                }
                
                if ($this -> m_bDispatchSignals)
                {
                        pcntl_signal_dispatch ();
                }
        }
        
        /**
         * This function catches the signal thrown by the linux shell, when
         * available. On some signals we want to initialise the shutdown process.
         * 
         * @param integer $nSignal The signal that has been received.
         */
        
        public function onCatchSignal ($nSignal)
        {
                echo 'Nuwani is shutting down...' . PHP_EOL;
                foreach ($this -> m_aBotList as $nBotIndex => $aBotInfo)
                {
                        $this -> unregister ($aBotInfo ['Instance']);
                }
                
                /** Initialize a waiting period so all socket operations can finish. **/
                for ($i = 0; $i < 4; $i ++)
                {
                        usleep (250000);
                }
                
                die ('Nuwani has been shutdown.' . PHP_EOL);
        }
        
        /**
         * This method returns the number of bots in the BotManager. A bot will
         * be destroyed as soon as it isn't usable anymore. That's the case when
         * it can't connect at all, or has been shut down manually.
         * 
         * @return integer
         */
        
        public function count ()
        {
                return count ($this -> m_aBotList);
        }
        
        /**
         * Returns a Bot or BotGroup with the defined conditionals in place, returns
         * false when no bots are found. First priority is checking for direct
         * matches, otherwise patterns will be checked.
         * 
         * A pattern can consist of one or more keys with values. The available
         * keys are:
         * * network - To check for bots which are connected to a specific network.
         * * channel - To check for bots in a specific channel.
         * There are also a couple of loose keywords, which define properties a
         * bot must have in order to be matched:
         * * master - A bot must be a master bot, which processes modules.
         * * slave  - A bot must be a slave bot, which is just an extra connection to an IRC server.
         * 
         * Keys can be used using the "key:value" syntax (without quotes). Keywords
         * are just the keywords themselves, without any fancy stuff. All conditionals
         * are to be seperated by spaces.
         * 
         * Examples:
         * "network:GTANet channel:#Nuwani" - Finds all bots in the channel #Nuwani on GTANet.
         * "network:GTANet master" - Finds the master bot on GTANet (note that this doesn't have to be only one bot, but in practise it usually is.)
         * "channel:#Nuwani" - Finds all bots which are in a channel called #Nuwani. Note that this is cross-network.
         * 
         * @param string $sKey Key of the entry that you want to receive.
         * @return BotGroup|Bot
         */
        
        public function offsetGet ($sKey)
        {
                $aChunks = explode (' ', strtolower ($sKey));
                $aMatch  = $aRequirements = array ();
                
                /** First decide the requirements for a bot to match **/
                foreach ($aChunks as $sChunk)
                {
                        if (strpos ($sChunk, ':') !== false)
                        {
                                list ($sKey, $sValue) = explode (':', $sChunk, 2);
                                if ($sKey == 'network' || $sKey == 'channel')
                                {
                                        $aRequirements [ucfirst ($sKey)] = $sValue;
                                }
                        }
                        else if ($sChunk == 'master' || $sChunk == 'slave')
                        {
                                $aRequirements [ucfirst ($sChunk)] = true;
                        }
                }
                
                /** And now see which bots match the requirements **/
                $bGotRequirements = count ($aRequirements) != 0;
                foreach ($this -> m_aBotList as $pBot)
                {
                        /** Direct nickname matching. **/
                        if ($pBot ['Nickname'] == $sKey)
                        {
                                $aMatch [] = & $pBot ['Instance'];
                                continue ;
                        }
                        
                        if (! $bGotRequirements)
                        {
                                /** No requirements, we want name matching. **/
                                continue ;
                        }
                        
                        if (isset ($aRequirements ['Network']) && strtolower ($pBot ['Instance']['Network']) != $aRequirements ['Network'])
                        {
                                /** Not on the required network. **/
                                continue ;
                        }
                        
                        if (isset ($aRequirements ['Channel']) && ! $pBot ['Instance'] -> inChannel ($aRequirements ['Channel']))
                        {
                                /** Not in the required channel. **/
                                continue ;
                        }
                        
                        if (isset ($aRequirements ['Slave']) && $pBot ['Instance']['Slave'] == false)
                        {
                                /** Not a slave, we want a slave. **/
                                continue ;
                        }
                        
                        if (isset ($aRequirements ['Master']) && $pBot ['Instance']['Slave'] == true)
                        {
                                /** We want a master, but this is a slave. **/
                                continue ;
                        }
                        
                        $aMatch [] = & $pBot ['Instance'];
                }
                
                $nMatches = count ($aMatch);
                if ($nMatches == 1)
                {
                        /** No need for a group if we're alone. **/
                        return array_pop ($aMatch);
                }
                else if ($nMatches == 0)
                {
                        return false;
                }
                
                return new BotGroup ($aMatch);
        }
        
        /**
         * In order to simply check if we got a bot with a certain nickname, we
         * can use the isset () call on the instance of the BotManager. The
         * advanced possibilities of offsetGet are not covered in this method.
         * If you want to check if there's a bot with some specific properties,
         * use the offsetGet method and check if the return value isn't false.
         * 
         * @param string $sKey Key of the entry that you want to check.
         * @return boolean
         */
        
        public function offsetExists ($sKey)
        {
                $sLowerKey = strtolower ($sKey);
                foreach ($this -> m_aBotList as $pBot)
                {
                        /** Direct nickname matching. **/
                        if (strtolower ($pBot ['Nickname']) == $sLowerKey)
                        {
                                return true ;
                        }
                }
                
                return false ;
        }
        
        /**
         * Creating new bots should be done with the register function, therefore
         * this function is NOT valid and will simply return null.
         * 
         * @param string $sKey Key of the entry that you want to set.
         * @param mixed $mValue Value to assign to the key.
         */
        
        public function offsetSet ($sKey, $mValue)
        {
                return ;
        }
        
        /**
         * Unsetting bots is something that should be done using the unregister
         * function, not by us, so therefore we simply return null.
         * 
         * @param string $sKey Key of the entry that you want to unset.
         */
        
        public function offsetUnset ($sKey)
        {
                return ;
        }
};

?>
