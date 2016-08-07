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

use Nuwani\Bot;
use Nuwani\ErrorExceptionHandler;

class Command implements Serializable, ArrayAccess
{
        /**
         * The actual command name, e.g. "test". The prefix is NOT included in this,
         * seeing we want to have that private.
         *
         * @var string
         */

        private $m_sCommand;

        /**
         * Contains the PHP code for the command or the callback. For performance
         * reasons this will be cached in another property, as an anonymous function.
         *
         * @var mixed
         */

        private $m_mCode;

        /**
         * Sets the security level associated with this command. Level is related
         * to the secutity providers which registered themselfes with the module.
         *
         * @var integer
         */

        private $m_nLevel;

        /**
         * The cached command is a anonymous function that'll execute the command's
         * code. Reason for this is that it's faster when cached.
         *
         * @var resource
         */

        private $m_rCachedCommand;

        /**
         * The networks on which this command will be executed on. If it's empty,
         * it will be executed on all networks. The networks should be supplied as
         * the name given to them in config.php.
         *
         * @var array
         */

        private $m_aNetworks;

        /**
         * The channels this command will be permitted to execute on. If it's empty,
         * all channels are allowed.
         *
         * @var array
         */

        private $m_aChannels;

        /**
         * The array holding all of the statistics of this command.
         *
         * @var array
         */

        private $m_aStatistics;

        /**
         * Whether this command should serialized and saved by the Commands module.
         * I wouldn't suggest this when using in external modules, since those get
         * reregistered on startup anyway. This property is also enforced on
         * non-serializable callbacks.
         *
         * @var boolean
         */

        private $m_bSave;

        /**
         * The constructor will initialise the basic variables in this class, and, incase
         * required and possible, initialise the cached handler as well.
         *
         * @param string $sCommand Command that should be executed.
         * @param mixed $mCode Code or callback that should be executed for the command.
         * @param integer $nLevel The security level needed to execute this command.
         * @param boolean $bSave Whether this command should be saved by the Commands module.
         */

        public function __construct ($sCommand, $mCode = null, $nLevel = 0, $bSave = false)
        {
                $this -> setCommand ($sCommand);
                $this -> setCode ($mCode);
                $this -> setSecurityLevel ($nLevel);
                $this -> setSave ($bSave);

                /** Defaults. **/
                $this -> m_aNetworks      = array ();
                $this -> m_aChannels      = array ();

                $this -> m_aStatistics    = array
                (
                        'Executed'        => 0,
                        'TotalTime'       => 0.0,
                        'LastTime'        => 0,
                );
        }

        /**
         * This function returns the name of the command which will be executed,
         * pretty much returning the m_sCommand property.
         *
         * @return string
         */

        public function getCommand ()
        {
                return $this -> m_sCommand;
        }

        /**
         * Returns the code or callback which has been associated with this command.
         * Making changes or whatsoever is not possible using this.
         *
         * @return mixed
         */

        public function getCode ()
        {
                return $this -> m_mCode;
        }

        /**
         * This function retuns the security level associated with this command,
         * enabling you to check whether this use can execute this.
         *
         * @return integer
         */

        public function getSecurityLevel ()
        {
                return $this -> m_nLevel;
        }

        /**
         * Tells us whether this command will be saved by the Commands module.
         *
         * @return boolean
         */

        public function getSave ()
        {
                return $this -> m_bSave;
        }

        /**
         * This function returns the networks on which this command is allowed
         * to execute.
         *
         * @return array
         */

        public function getNetworks ()
        {
                return $this -> m_aNetworks;
        }

        /**
         * This function will return an array with the channels this command is
         * allowed to execute in.
         *
         * @return array
         */

        public function getChannels ()
        {
                return $this -> m_aChannels;
        }

        /**
         * Retrieves the array containing the statistics of this command.
         *
         * @return array
         */

        public function getStatistics ()
        {
                return $this -> m_aStatistics;
        }

        /**
         * This function will set the actual command's name, to change it's name this function
         * should be called. Purely for internal reference sake though.
         *
         * @param string $sCommand New name of this command.
         */

        public function setCommand ($sCommand)
        {
                $this -> m_sCommand = $sCommand;
        }

        /**
         * This function will update the code associated with this command. The caching will
         * automatically be re-initialised for performance reasons.
         *
         * @param mixed $mCode Code or callback this command should be executing.
         */

        public function setCode ($mCode)
        {
                $this -> m_mCode = $mCode;

                if ($this -> m_mCode != null)
                {
                        $this -> cache ();
                }
        }

        /**
         * A simple function which allows you to update the security level required
         * to execute this command properly, will be checked externally.
         *
         * @param integer $nLevel Security Level to apply to this command.
         */

        public function setSecurityLevel ($nLevel)
        {
                $this -> m_nLevel = $nLevel;
        }

        /**
         * Sets this command to be serialized and saved to file by the Commands. This
         * command has to be registered with the Commands module in order to be able
         * to do that, however. This property will also be forced to false when a
         * non-serializable callback is used, that is, anything other than a string
         * of code.
         *
         * @param boolean $bSave Whether to save or not.
         */

        public function setSave ($bSave)
        {
                /** We don't like being set to true, only if we have actual code within us. **/
                $this -> m_bSave = false;

                /** The is_string() check is to check against failing array callbacks. **/
                if (!is_callable ($this -> m_mCode) && is_string ($this -> m_mCode))
                {
                        $this -> m_bSave = $bSave;
                }
        }

        /**
         * This function will let you add all the networks you want to give this
         * command at once.
         *
         * @param array $aNetworks The networks to apply to this command.
         */

        public function setNetworks ($aNetworks)
        {
                if (count ($aNetworks) == 1 && $aNetworks [0] == '-')
                {
                        return $this -> m_aNetworks = array ();
                }

                $this -> m_aNetworks = $aNetworks;
        }

        /**
         * Adds a single network entry to the networks array.
         *
         * @param string $sNetwork The network to add to this command.
         */

        public function addNetwork ($sNetwork)
        {
                if (!in_array ($sNetwork, $this -> m_aNetworks))
                {
                        $this -> m_aNetworks [] = $sNetwork;
                }
        }

        /**
         * This function checks if this command if allowed to execute on the given
         * network.
         *
         * @param string $sNetwork The network to check for.
         * @return boolean
         */

        public function checkNetwork ($sNetwork)
        {
                if (empty ($this -> m_aNetworks))
                {
                        return true;
                }

                return in_array ($sNetwork, $this -> m_aNetworks);
        }

        /**
         * This function allows you to set the array of channels in which this
         * command is allowed to execute in.
         *
         * @param array $aChannels The channels to apply to this command.
         */

        public function setChannels ($aChannels)
        {
                if (count ($aChannels) == 1 && $aChannels [0] == '-')
                {
                        return $this -> m_aChannels = array ();
                }

                $this -> m_aChannels = array_map ('strtolower', $aChannels);
        }

        /**
         * This function lets you add a channel to the array of allowed channels.
         *
         * @param string $sChannel The channel to add.
         */

        public function addChannel ($sChannel)
        {
                $sChannel = strtolower ($sChannel);

                if (!in_array ($sChannel, $this -> m_aChannels))
                {
                        $this -> m_aChannels [] = $sChannel;
                }
        }

        /**
         * This function checks if this command is allowed to execute in the
         * given channel.
         *
         * @param string $sChannel The channel to check.
         * @return boolean
         */

        public function checkChannel ($sChannel)
        {
                if (empty ($this -> m_aChannels))
                {
                        return true;
                }

                return in_array (strtolower ($sChannel), $this -> m_aChannels);
        }

        /**
         * The cache function will cache the actual command's code, to make sure the
         * performance loss of eval() only occurs once, rather than every time.
         *
         * @throws Exception When somehow the callback couldn't be created.
         */

        private function cache ()
        {
                if (is_callable ($this -> m_mCode))
                {
                        /** Nothing to be done. **/
                        $this -> m_rCachedCommand = $this -> m_mCode;
                }
                else if (is_string ($this -> m_mCode))
                {
                        /** Try to create a callback from the code. **/
                        $rCachedCommand = @ create_function ('$pBot, $sDestination, $sChannel, $sNickname, $aParams, $sMessage', $this -> m_mCode);

                        if ($rCachedCommand === false)
                        {
                                /** Syntax error in the code. **/
                                throw new Exception ('Error creating callback from given code.');
                        }

                        $this -> m_rCachedCommand = $rCachedCommand;
                }
                else
                {
                        /** We can't do anything with this. **/
                        throw new Exception ('Couldn\'t create a Command from the supplied parameters.');
                }
        }

        /**
         * This function returns this command in a serialized form, so it can be stored
         * in a file and re-created later on, using the unserialize function (suprise!).
         *
         * @return string
         */

        public function serialize ()
        {
                return serialize (array
                (
                        false,
                        $this -> m_sCommand,
                        $this -> m_mCode,
                        $this -> m_nLevel,
                        $this -> m_aNetworks,
                        $this -> m_aChannels,
                        $this -> m_aStatistics
                ));
        }

        /**
         * The unserialize method will, yes, unserialize a previously serialized command
         * so we can use it again. Quite convenient for various reasons.
         */

        public function unserialize ($sData)
        {
                $aInformation = unserialize ($sData);

                $this -> m_sCommand  = $aInformation [1];
                $this -> m_mCode     = $aInformation [2];
                $this -> m_nLevel    = $aInformation [3];

                /** These if-statements are all for backwards compatibility. **/
                if (isset ($aInformation [4]))
                {
                        $this -> m_aNetworks = $aInformation [4];
                }

                if (isset ($aInformation [6]))
                {
                        $this -> m_aChannels   = $aInformation [5];
                        $this -> m_aStatistics = $aInformation [6];
                }
                else if (isset ($aInformation [5]))
                {
                        $this -> m_aStatistics = $aInformation [5];
                }

                $this -> cache ();
        }

        /**
         * The invoking function which allows us to use fancy syntax for commands. It allows the user
         * and bot-system to directly invoke the object variable.
         *
         * @param array $aArguments Arguments as passed on to this command.
         */

        public function __invoke ($pBot, $sDestination, $sChannel, $sNickname, $aParams, $sMessage)
        {
                if (is_callable ($this -> m_rCachedCommand)   &&
                    $this -> checkNetwork ($pBot ['Network']) &&
                    $this -> checkChannel ($sChannel))
                {
                        $cFunction = $this -> m_rCachedCommand;

                        $this -> m_aStatistics ['Executed'] ++;
                        $this -> m_aStatistics ['LastTime'] = time ();

                        /** Let the exception handler know where we are executing code. **/
                        ErrorExceptionHandler :: $Source = $sDestination;

                        /** Catch output. **/
                        ob_start ();

                        $fStart = microtime (true);

                        /** Execute the command. **/
                        call_user_func ($cFunction, $pBot, $sDestination, $sChannel, $sNickname, $aParams, $sMessage);

                        $this -> m_aStatistics ['TotalTime'] += microtime (true) - $fStart;

                        /** Send output to IRC. **/
                        $aOutput = explode ("\n", trim (ob_get_clean ()));
                        if (isset ($pBot) && $pBot instanceof Bot)
                        {
                                foreach ($aOutput as $sLine)
                                {
                                        $pBot -> send ('PRIVMSG ' . $sDestination . ' :' . trim ($sLine));
                                }
                        }

                        return true;
                }

                return false;
        }

        /**
         * This magic method enables this object to be echo'd, without calling
         * methods. Useful for quick retrieval of which command this is.
         *
         * @return string
         */

        public function __toString ()
        {
                return $this -> m_sCommand;
        }

        // -------------------------------------------------------------------//
        // Region: ArrayAccess                                                //
        // -------------------------------------------------------------------//

        /**
         * Check whether the offset exists within this command.
         *
         * @param string $sOffset The setting or statistic to check.
         * @return boolean
         */
        public function offsetExists ($sOffset)
        {
                return (in_array ($sOffset, array ('Command', 'Code', 'Level', 'Networks', 'Save')) ||
                        isset ($this -> m_aStatistics [$sOffset]));
        }

        /**
         * Gets a specific setting or statistic of this command. Returns false
         * if no setting or statistic has been found.
         *
         * @param string $sOffset The setting or statistic to get.
         * @return mixed
         */
        public function offsetGet ($sOffset)
        {
                switch ($sOffset)
                {
                        case 'Command':  { return $this -> getCommand ();       }
                        case 'Code':     { return $this -> getCode ();          }
                        case 'Level':    { return $this -> getSecurityLevel (); }
                        case 'Networks': { return $this -> getNetworks ();      }
                        case 'Channels': { return $this -> getChannels ();      }
                        case 'Save':     { return $this -> getSave ();          }
                }

                if (isset ($this -> m_aStatistics [$sOffset]))
                {
                        return $this -> m_aStatistics [$sOffset];
                }

                return false;
        }

        /**
         * Quickly set a certain setting for this command.
         *
         * @param string $sOffset The setting to set.
         * @param mixed $mValue The value.
         */
        public function offsetSet ($sOffset, $mValue)
        {
                switch ($sOffset)
                {
                        case 'Command':  { $this -> setCommand ($mValue);        break; }
                        case 'Code':     { $this -> setCode ($mValue);           break; }
                        case 'Level':    { $this -> setSecurityLevel ($mValue);  break; }
                        case 'Networks': { $this -> setNetworks ($mValue);       break; }
                        case 'Channels': { $this -> setChannels ($mValue);       break; }
                        case 'Save':     { $this -> setSave ($mValue);           break; }
                }
        }

        /**
         * This is very much not allowed, since setting defaults could break the
         * command or even more.
         *
         * @param string $sOffset The setting to unset.
         */
        public function offsetUnset ($sOffset)
        {
                return;
        }
}
