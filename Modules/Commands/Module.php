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
 * @package Commands Module
 * @author Peter Beverloo <peter@lvp-media.com>
 * @author Dik Grapendaal <dik@sa-mp.nl>
 * @see http://nuwani.googlecode.com
 */

require_once 'Modules/Commands/Command.php';

use Nuwani \ Bot;

class Commands extends ModuleBase implements ISecurityModule, ArrayAccess, Countable, IteratorAggregate
{
        /**
         * This property contains an array with all commands which have been registered with
         * this bot. Various information will be included as well.
         */
        
        private $m_aCommands;
        
        /**
         * A single character which defines the prefix of all commands that will be used. Do
         * NOT use multiple characters in here, seeing everything will totally break.
         */
        
        private $m_sPrefix;
        
        /**
         * A boolean which indicates whether all commands should be handled with case
         * sensitivity. If true, then !hello and !Hello are considered different commands.
         */
        
        private $m_bCaseSensitive;
        
        /**
         * Defines an array with security-level providers, seeing we don't have any level
         * system ourselves, other modules are free to register theirs.
         */
        
        private $m_aSecurityProviders;
        
        /**
         * In the constructor of this function we'll attempt to load the data file associated
         * with this module, containing all its current commands.
         */
        
        public function __construct ()
        {
                $this -> m_aSecurityProviders   = array ();
                $this -> m_aCommands            = array ();
                $this -> m_sPrefix              = '!';
                $this -> m_bCaseSensitive       = true;
                
                /** Make the module somewhat useful by introducing some basic commands. **/
                $this -> registerInternalCommands ();
                
                /** Load up the user's commands. **/
                if (file_exists ('Data/Commands.dat'))
                {
                        $aInformation = unserialize (file_get_contents ('Data/Commands.dat'));
                        
                        foreach ($aInformation [0] as $sName => $pCommand)
                        {
                                if ($pCommand instanceof Command)
                                {
                                        $pCommand ['Save'] = true;
                                        
                                        $sName = $this -> getCommandName ($sName);
                                        $this -> m_aCommands [$sName] = $pCommand;
                                }
                        }
                        
                        $this -> m_sPrefix = $aInformation [1];
                        if (isset ($aInformation [2]))
                        {
                                $this -> m_bCaseSensitive = $aInformation [2];
                        }
                }
                
                /** Save any changes or create the file if it doesn't exist. **/
                $this -> save ();
        }
        
        /**
         * The destructor saves all commands again, just to be sure we have all changes.
         */
        
        public function __destruct ()
        {
                $this -> save ();
        }
        
        /**
         * The save function will serialize all this bot's data into a file, so it can be
         * retrieved for later use without any inconvenience. No need to call this
         * manually; it'll be done after each modification to the m_aCommands array.
         * 
         * @return boolean Indicates whether everything could be saved.
         */
        
        private function save ()
        {
                $aStorageList = array ();
                
                foreach ($this -> m_aCommands as $sName => $pCommandInfo)
                {
                        if ($pCommandInfo ['Save'] == false)
                        {
                                continue;
                        }
                        
                        $sName = $this -> getCommandName ($sName);
                        $aStorageList [$sName] = $pCommandInfo;
                }
                
                return (file_put_contents ('Data/Commands.dat', serialize (array (
                        $aStorageList, $this -> m_sPrefix, $this -> m_bCaseSensitive))) !== false);
        }
        
        /**
         * This method registers a couple of commands which can be used by the
         * bot owner to control one another from IRC, like adding, removing and
         * renaming, changing properties of commands, (un)loading modules, etc.
         * Just have a skim through this method and you'll figure it out.
         */
        
        private function registerInternalCommands ()
        {
                $this -> registerCommand (new Command ('cmdadd',
                        function ($pBot, $sDestination, $sChannel, $sNickname, $aParams, $sMessage)
                        {
                                if (count ($aParams) < 2)
                                {
                                        echo '10* Usage: !cmdadd CommandName Code';
                                }
                                else
                                {
                                        Nuwani \ ModuleManager :: getInstance () -> offsetGet ('Commands')
                                                -> addCommand (array_shift ($aParams), implode (' ', $aParams));
                                        echo '3* Success: The command has been added.';
                                }
                        },
                        ISecurityProvider :: BOT_OWNER
                ))
                -> registerCommand (new Command ('cmdremove',
                        function ($pBot, $sDestination, $sChannel, $sNickname, $aParams, $sMessage)
                        {
                                if (count ($aParams) != 1)
                                        echo '10* Usage: !cmdremove CommandName';
                                else
                                {
                                        if (!Nuwani \ ModuleManager :: getInstance () -> offsetGet ('Commands') -> deleteCommand ($aParams [0]))
                                                echo '4* Error: The command has not been found.';
                                        else
                                                echo '3* Success: The command has been deleted successfully.';
                                }
                        },
                        ISecurityProvider :: BOT_OWNER
                ))
                -> registerCommand (new Command ('cmdrename',
                        function ($pBot, $sDestination, $sChannel, $sNickname, $aParams, $sMessage)
                        {
                                if (count ($aParams) != 2)
                                        echo '10* Usage: !cmdrename OldName NewName';
                                else
                                {
                                        if (!Nuwani \ ModuleManager :: getInstance () -> offsetGet ('Commands') -> renameCommand ($aParams [0], $aParams [1]))
                                                echo '4* Error: The command has not been found.';
                                        else
                                                echo '3* Success: The command has been renamed successfully.';
                                }
                        },
                        ISecurityProvider :: BOT_OWNER
                ))
                -> registerCommand (new Command ('cmdchannel',
                        function ($pBot, $sDestination, $sChannel, $sNickname, $aParams, $sMessage)
                        {
                                if (count ($aParams) < 2)
                                        echo '10* Usage: !cmdchannel CommandName [- / Channel1 14ChannelN]';
                                else
                                {
                                        $c = Nuwani \ ModuleManager :: getInstance () -> offsetGet ('Commands') -> offsetGet ($aParams [0]);
                                        if ($c)
                                        {
                                                $c -> setChannels (array_slice ($aParams, 1));
                                                echo '3* Success: The channels have been updated successfully.';
                                        }
                                        else
                                                echo '4* Error: The command has not been found.';
                                }
                        },
                        ISecurityProvider :: BOT_OWNER
                ))
                -> registerCommand (new Command ('cmdnetwork',
                        function ($pBot, $sDestination, $sChannel, $sNickname, $aParams, $sMessage)
                        {
                                if (count ($aParams) < 2)
                                        echo '10* Usage: !cmdnetwork CommandName [- / Network1 14NetworkN]';
                                else
                                {
                                        $c = Nuwani \ ModuleManager :: getInstance () -> offsetGet ('Commands') -> offsetGet ($aParams [0]);
                                        if ($c)
                                        {
                                                $c -> setNetworks (array_slice ($aParams, 1));
                                                echo '3* Success: The networks have been updated successfully.';
                                        }
                                        else
                                                echo '4* Error: The command has not been found.';
                                }
                        },
                        ISecurityProvider :: BOT_OWNER
                ))
                -> registerCommand (new Command ('cmdcode',
                        function ($pBot, $sDestination, $sChannel, $sNickname, $aParams, $sMessage)
                        {
                                if (count ($aParams) != 1)
                                        echo '10* Usage: !cmdcode CommandName';
                                else
                                {
                                        $c = Nuwani \ ModuleManager :: getInstance () -> offsetGet ('Commands') -> offsetGet ($aParams [0]);
                                        if ($c)
                                        {
                                                if (is_string ($c -> getCode ()))
                                                        echo $c -> getCode ();
                                                else
                                                        echo '4* Error: The command is a function call, no code can be displayed.';
                                        }
                                        else
                                                echo '4* Error: The command has not been found.';
                                }
                        },
                        ISecurityProvider :: BOT_OWNER
                ))
                -> registerCommand (new Command ('cmdlist',
                        function ($pBot, $sDestination, $sChannel, $sNickname, $aParams, $sMessage)
                        {
                                $aCmd = array ();
                                foreach (Nuwani \ ModuleManager :: getInstance () -> offsetGet ('Commands') -> getIterator () as $sName => $pCommand)
                                        $aCmd [] = $sName;
                                
                                echo wordwrap ('10* Commands (' . count (Nuwani \ ModuleManager :: getInstance () -> offsetGet ('Commands')) . '): ' . implode (', !', $aCmd), 400);
                        },
                        ISecurityProvider :: BOT_OWNER
                ))
                -> registerCommand (new Command ('moduleload',
                        function ($pBot, $sDestination, $sChannel, $sNickname, $aParams, $sMessage)
                        {
                                if (count ($aParams) != 1)
                                        echo '10* Usage: !moduleload ModuleName';
                                else
                                {
                                        if (Nuwani \ ModuleManager :: getInstance () -> loadModule ($aParams [0]))
                                                echo '3* Success: The module has been loaded.';
                                        else
                                                echo '4* Error: The module could not be loaded.';
                                }
                        },
                        ISecurityProvider :: BOT_OWNER
                ))
                -> registerCommand (new Command ('moduleunload',
                        function ($pBot, $sDestination, $sChannel, $sNickname, $aParams, $sMessage)
                        {
                                if (count ($aParams) != 1)
                                        echo '10* Usage: !moduleunload ModuleName';
                                else
                                {
                                        if (Nuwani \ ModuleManager :: getInstance () -> unloadModule ($aParams [0]))
                                                echo '3* Success: The module has been unloaded.';
                                        else
                                                echo '4* Error: The module could not be unloaded.';
                                }
                        },
                        ISecurityProvider :: BOT_OWNER
                ))
                -> registerCommand (new Command ('modulereload',
                        function ($pBot, $sDestination, $sChannel, $sNickname, $aParams, $sMessage)
                        {
                                if (count ($aParams) != 1)
                                        echo '10* Usage: !modulereload ModuleName';
                                else
                                {
                                        if (Nuwani \ ModuleManager :: getInstance () -> reloadModule ($aParams [0]))
                                                echo '3* Success: The module has been reloaded.';
                                        else
                                                echo '4* Error: The module could not be reloaded.';
                                }
                        },
                        ISecurityProvider :: BOT_OWNER
                ))
                -> registerCommand (new Command ('modulelist',
                        function ($pBot, $sDestination, $sChannel, $sNickname, $aParams, $sMessage)
                        {
                                $c = Nuwani \ ModuleManager :: getInstance ();
                                $aModules = array ();
                                foreach ($c as $sName => $pModule)
                                        $aModules [] = $sName;
                                
                                echo '10* Modules (' . count ($c) . '): ' . implode (', ', $aModules);
                        },
                        ISecurityProvider :: BOT_OWNER
                ))
                -> registerCommand (new Command ('botlist',
                        function ($pBot, $sDestination, $sChannel, $sNickname, $aParams, $sMessage)
                        {
                                $c = Nuwani \ BotManager :: getInstance () -> getBotList ();
                                $aBots = array ();
                                foreach ($c as $sName => $Bot)
                                        $aBots [] = $sName . ' (' . $Bot ['Network'] . ')';
                                
                                echo '10* Bots (' . count ($c) . '): ' . implode (', ', $aBots);
                        },
                        ISecurityProvider :: BOT_OWNER
                ))
                -> registerCommand (new Command ('meminfo',
                        function ($pBot, $sDestination, $sChannel, $sNickname, $aParams, $sMessage)
                        {
                                $aGarbage = Nuwani \ Memory :: getStatistics ();
                                echo '10* Current usage: ', sprintf ('%.2f MB', memory_get_usage () / 1024 / 1024),
                                        ' | 10Top usage: ', sprintf ('%.2f MB', memory_get_peak_usage () / 1024 / 1024),
                                        ' | 10Garbage: ', sprintf ('%.2f kB', $aGarbage ['Memory']);
                        },
                        ISecurityProvider :: BOT_OWNER
                ))
                -> registerCommand (new Command ('restart',
                        function ($pBot, $sDestination, $sChannel, $sNickname, $aParams, $sMessage)
                        {
                                Nuwani \ BotManager :: getInstance () -> getBotList () -> send ('QUIT :Restart requested by ' . $sNickname);
                                usleep (150000);
                                
                                die (exec ($_SERVER ['_'] . ' run.php restart > /dev/null &'));
                        },
                        ISecurityProvider :: BOT_OWNER
                ))
                -> registerCommand (new Command ('cmdlevel',
                        function ($pBot, $sDestination, $sChannel, $sNickname, $aParams, $sMessage)
                        {
                                if (count ($aParams) != 2)
                                        echo '10* Usage: !cmdlevel command level';
                                else
                                {
                                        if (!Nuwani \ ModuleManager :: getInstance () -> offsetGet ('Commands') -> setCommandLevel ($aParams [0], $aParams [1]))
                                                echo '4* Error: The command has not been found.';
                                        else
                                                echo '3* Success: The required level has been updated successfully.';
                                }
                        },
                        ISecurityProvider :: BOT_OWNER
                ));
        }
        
        /**
         * To instantly shift all commands from "!time" to "?time", this is the function that
         * has to be used. It'll automatically serialize the new prefix too.
         * 
         * @param string $sPrefix Prefix to assign to the commands.
         * @return boolean Indicates whether everything could be saved.
         */
        
        public function setPrefix ($sPrefix)
        {
                if (strlen ($sPrefix) != 1)
                {
                        return false;
                }
                
                $this -> m_sPrefix = $sPrefix;
                
                return $this -> save ();
        }
        
        /**
         * Sets whether this module should handle all commands with case sensitivity or not.
         * If set to false, all commands will be re-added so that case insensitivity 
         * immediately works. The Command :: m_sCommand property is deliberately left as-is;
         * this makes the case sensitivity reversible (which is done when bCaseSensitive is
         * set to true).
         * 
         * @param boolean $bCaseSensitive Case sensitive, true or false.
         * @return boolean Indicates whether everything could be saved.
         */
        
        public function setCaseSensitive ($bCaseSensitive)
        {
                $this -> m_bCaseSensitive = $bCaseSensitive;
                
                $aNewCommands = array ();
                foreach ($this -> m_aCommands as $sCommand => $pCommand)
                {
                        if ($bCaseSensitive)
                        {
                                $sCommand = $pCommand -> getCommand ();
                        }
                        else
                        {
                                $sCommand = strtolower ($sCommand);
                        }
                        
                        $aNewCommands [$sCommand] = $pCommand;
                }
                
                $this -> m_aCommands = $aNewCommands;
                
                return $this -> save ();
        }
        
        /**
         * The function which allows you to create a new command with the handler,
         * so new fancy features can be added to the bot system.
         * 
         * @param string $sCommand Name of the command that you wish to implement.
         * @param string $sCode Code to be associated with this command.
         * @return boolean Indicates whether everything could be saved.
         */
        
        public function addCommand ($sCommand, $sCode)
        {
                $sCommand = $this -> getCommandName ($sCommand);
                
                $this -> m_aCommands [$sCommand] = new Command ($sCommand, $sCode, 0, true);
                
                return $this -> save ();
        }
        
        /**
         * This method will allow an external source (most likely a module) to
         * register a predefined Command object with this module. This will be
         * added to the main commands array and executed as any other.
         * 
         * @param Command $pCommand Command object with the code that should be executed when invoked.
         * @return Commands Pointer to this module, to enable method chaining.
         */
        
        public function registerCommand (Command $pCommand)
        {
                $sCommand = $this -> getCommandName ($pCommand ['Command']);
                
                $this -> m_aCommands [$sCommand] = $pCommand;
                
                if ($pCommand ['Save'])
                {
                        $this -> save ();
                }
                
                return $this;
        }
        
        /**
         * This function will rename a currently listed command into something new,
         * so it can be used properly. 
         * 
         * @param string $sOldCommand Current name of the command.
         * @param string $sNewCommand New name of the command.
         * @return boolean Indicates whether everything could be saved.
         */
        
        public function renameCommand ($sOldCommand, $sNewCommand)
        {
                $sOldCommand = $this -> getCommandName ($sOldCommand);
                $sNewCommand = $this -> getCommandName ($sNewCommand);
                
                if (!isset ($this -> m_aCommands [$sOldCommand]))
                {
                        return false;
                }
                
                $this -> m_aCommands [$sNewCommand] = $this -> m_aCommands [$sOldCommand];
                unset ($this -> m_aCommands [$sOldCommand]);
                
                return $this -> save ();
        }
        
        /**
         * This function can be used to totally remove a command from our system.
         * Mind that no backups are made and that this is being applied immediately.
         * 
         * @param string $sCommand Command that you want to completely remove.
         * @return boolean Indicates whether everything could be saved.
         */
        
        public function deleteCommand ($sCommand)
        {
                $sCommand = $this -> getCommandName ($sCommand);
                
                if (!isset ($this -> m_aCommands [$sCommand]))
                {
                        return false;
                }
                
                unset ($this -> m_aCommands [$sCommand]);
                return $this -> save ();
        }
        
        /**
         * This function lets you retrieve the object of a command. Returns false
         * if the command does not exist.
         * 
         * @param string $sCommand The command of which you want the object.
         * @return Command|false
         */
        
        public function getCommand ($sCommand)
        {
                $sCommand = $this -> getCommandName ($sCommand);
                
                if (isset ($this -> m_aCommands [$sCommand]))
                {
                        return $this -> m_aCommands [$sCommand];
                }
                
                return false;
        }
        
        /**
         * This method check whether perhaps the given command has been prefixed
         * by the prefix ($this -> m_sPrefix), and if so, removes it, and returns
         * the commandname without it.
         * 
         * @param string $sCommand The command of which you want the name.
         * @return string The clean command name.
         */
        
        private function getCommandName ($sCommand)
        {
                if ($sCommand [0] == $this -> m_sPrefix)
                {
                        // Common mistake to include the prefix in the Command.
                        $sCommand = substr ($sCommand, 1);
                }
                
                if (!$this -> m_bCaseSensitive)
                {
                        $sCommand = strtolower ($sCommand);
                }
                
                return $sCommand;
        }
        
        /**
         * This function can be used to update the required security level of
         * the given command.
         * 
         * @param string $sCommand Command to update the level of.
         * @param integer $nLevel Required level for executing this command.
         */
        
        public function setCommandLevel ($sCommand, $nLevel)
        {
                $sCommand = $this -> getCommandName ($sCommand);
                
                if (isset ($this -> m_aCommands [$sCommand]))
                {
                        $this -> m_aCommands [$sCommand] -> setSecurityLevel ($nLevel);
                        return $this -> save ();
                }
                
                return false ;
        }
        
        /**
         * PHP Code evaluations may occur in public channels, such as #Sonium which
         * is frequently used for testing. This function will check whether we're
         * allowed to evaluate anything here.
         * 
         * @param Bot $pBot The bot who received the public channel message.
         * @param string $sChannel Channel in which we received the message.
         * @param string $sNickname The nickname associated with this message.
         * @param string $sMessage And of course the actual message we received.
         */
        
        public function onChannelPrivmsg (Bot $pBot, $sChannel, $sNickname, $sMessage)
        {
                if ($sMessage [0] == $this -> m_sPrefix)
                {
                        return $this -> handleCommand ($pBot, $sChannel, $sNickname, $sMessage);
                }
                
                $sAlternativePrefix = $pBot ['Nickname'] . ': ' . $this -> m_sPrefix;
                if (substr ($sMessage, 0, strlen ($sAlternativePrefix)) == $sAlternativePrefix)
                {
                        return $this -> handleCommand ($pBot, $sChannel, $sNickname, substr ($sMessage, strlen ($sAlternativePrefix) - 1));
                }
        }
        
        /**
         * People are free to send private messages to the bot, which gets
         * handled right here. This function does the same as onChannelPrivmsg.
         * 
         * @param string $pBot Bot that received this private message.
         * @param string $sNickname Source of the message.
         * @param string $sMessage The message that got PM'ed to us.
         */
        
        public function onPrivmsg (Bot $pBot, $sNickname, $sMessage)
        {
                if ($sMessage [0] == $this -> m_sPrefix)
                {
                        return $this -> handleCommand ($pBot, false, $sNickname, $sMessage);
                }
        }
        
        /**
         * This function determines whether we have to execute this command,
         * or not. The only hardcoded command is !addcmd.
         * 
         * @param Bot $pBot The bot who received the public channel message.
         * @param string $sChannel Channel in which we received the message.
         * @param string $sNickname The nickname associated with this message.
         * @param string $sMessage And of course the actual message we received.
         */
        
        private function handleCommand (Bot $pBot, $sChannel, $sNickname, $sMessage)
        {
                $aArguments = preg_split ('/(\s+)/', $sMessage);
                $sCommand   = $this -> getCommandName (array_shift ($aArguments));
                
                if (isset ($this -> m_aCommands [$sCommand]))
                {
                        $sDestination = $sChannel === false ? $sNickname : $sChannel;
                        $nSecurityLevel = $this -> m_aCommands [$sCommand] -> getSecurityLevel ();
                        
                        if ($nSecurityLevel != -1 &&
                            isset ($this -> m_aSecurityProviders [$nSecurityLevel]) &&
                            $this -> m_aSecurityProviders [$nSecurityLevel] -> checkSecurity ($pBot, $nSecurityLevel) === false)
                        {
                                return false;
                        }
                        
                        $sMessage = trim (substr ($sMessage, strlen ($sCommand) + 2));
                        if (strlen ($sMessage) == 0)
                        {
                                $sMessage = null;
                        }
                        
                        $this -> m_aCommands [$sCommand] ($pBot, $sDestination, $sChannel, $sNickname, $aArguments, $sMessage);
                        
                        return true;
                }
                
                return false;
        }
        
        /**
         * This function will register a new security provider with this very module,
         * as soon as it gets available. Will be called automatically.
         * 
         * @param ISecurityProvider $pProvider The security provider to register with this module.
         * @param integer $nLevel Security level this provider will register.
         */
        
        public function registerSecurityProvider (ISecurityProvider $pProvider, $nLevel)
        {
                $this -> m_aSecurityProviders [$nLevel] = $pProvider;
        }
        
        /**
         * Will be called when a security provider unloads, so we will be aware that we
         * won't be able to use it anymore. Could be for any reason.
         * 
         * @param ISecurityProvider $pProvider Security Provider that is being unloaded.
         */
        
        public function unregisterSecurityProvider (ISecurityProvider $pProvider)
        {
                foreach ($this -> m_aSecurityProviders as $nLevel => $pInstance)
                {
                        if ($pInstance == $pProvider)
                        {
                                unset ($this -> m_aSecurityProviders [$nLevel]);
                        }
                }
        }
        
        /**
         * This method returns the number of commands currently loaded.
         * 
         * @return integer
         */
        
        public function count ()
        {
                return count ($this -> m_aCommands);
        }
        
        /**
         * Returns an ArrayIterator for m_aCommands, which can be used in a
         * foreach statement.
         * 
         * @return ArrayIterator
         */
        
        public function getIterator ()
        {
                return new ArrayIterator ($this -> m_aCommands);
        }
        
        // -------------------------------------------------------------------//
        // Region: ArrayAccess                                                //
        // -------------------------------------------------------------------//
        
        /**
         * Check whether the offset exists within this module.
         * 
         * @param string $sOffset The command or setting to check.
         * @return boolean Indicating whether the offset exists.
         */
        
        public function offsetExists ($sOffset)
        {
                if ($sOffset == 'Prefix')
                {
                        return true;
                }
                
                if ($sOffset == 'CaseSensitive')
                {
                        return true;
                }
                
                return isset ($this -> m_aCommands [$sOffset]);
        }
        
        /**
         * Gets a specific setting of this module or the object of a command.
         * If no bots are found, then this function tries to match patterns.
         * Returns false if nothing has been found.
         * 
         * @param string $sOffset The command or setting to get.
         * @return mixed|false The value of the command or setting.
         */
        
        public function offsetGet ($sOffset)
        {
                if ($sOffset == 'Prefix')
                {
                        return $this -> m_sPrefix;
                }
                
                if ($sOffset == 'CaseSensitive')
                {
                        return $this -> m_bCaseSensitive;
                }
                
                if (isset ($this -> m_aCommands [$sOffset]))
                {
                        return $this -> getCommand ($sOffset);
                }
                
                if ($sOffset[0] == $this -> m_sPrefix &&
                    isset ($this -> m_aCommands [substr ($sOffset, 1)]))
                {
                        return $this -> getCommand (substr ($sOffset, 1));
                }
                
                $aChunks = explode(' ', strtolower ($sOffset));
                $aMatch  = $aReq = array ();
                
                /** Extract information from the pattern **/
                foreach ($aChunks as $sChunk)
                {
                        if (substr ($sChunk, 0, 5) == 'level')
                        {
                                $aReq ['level'] = array ($sChunk[5], (int) substr ($sChunk, 6));
                        }
                        else if (strpos ($sChunk, ':') !== false)
                        {
                                list ($sKey, $sValue) = explode (':', $sChunk);
                                $aReq [$sKey] = $sValue;
                        }
                }
                
                /** Let's see if there are commands which match **/
                foreach ($this -> m_aCommands as $sName => $pCmd)
                {
                        if (isset ($aReq ['level']))
                        {
                                $nLevel = $aReq ['level'] [1];
                                switch ($aReq ['level'] [0])
                                {
                                        case '=':
                                        {
                                                if ($pCmd ['Level'] == $nLevel)
                                                {
                                                        $aMatch [] = $this -> m_aCommands [$sName];
                                                }
                                                
                                                break;
                                        }
                                        
                                        case '<':
                                        {
                                                if ($pCmd ['Level'] < $nLevel)
                                                {
                                                        $aMatch [] = $this -> m_aCommands [$sName];
                                                }
                                                
                                                break;
                                        }
                                        
                                        case '>':
                                        {
                                                if ($pCmd ['Level'] > $nLevel)
                                                {
                                                        $aMatch [] = $this -> m_aCommands [$sName];
                                                }
                                                
                                                break;
                                        }
                                }
                        }
                        
                        if (isset ($aReq ['name']) && strpos ($pCmd, $aReq ['name']) !== false)
                        {
                                $aMatch [] = $this -> m_aCommands [$sName];
                        }
                        
                        if (isset ($aReq ['network']) && $pCmd -> checkNetwork ($aReq ['network']))
                        {
                                $aMatch [] = $this -> m_aCommands [$sName];
                        }
                }
                
                if (empty ($aMatch))
                {
                        return false;
                }
                
                return $aMatch;
        }
        
        /**
         * This is a shortcut to addCommand().
         * 
         * @param string $sOffset The command to set.
         * @param mixed $mValue The value.
         */
        
        public function offsetSet ($sOffset, $mValue)
        {
                if ($sOffset == 'Prefix')
                {
                        return $this -> setPrefix ($mValue);
                }
                
                if ($sOffset == 'CaseSensitive')
                {
                        return $this -> setCaseSensitive ($mValue);
                }
                
                $this -> addCommand ($sOffset, $mValue);
        }
        
        /**
         * This is a shortcut to deleteCommand().
         * 
         * @param string $sOffset The command to unset.
         */
        
        public function offsetUnset ($sOffset)
        {
                $this -> deleteCommand ($sOffset);
        }
        
};

?>
