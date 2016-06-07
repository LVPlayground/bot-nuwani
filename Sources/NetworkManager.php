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
 * @version $Id: NetworkManager.php 136 2011-02-23 01:23:09Z dik.grapendaal $
 * @package Nuwani
 */
 
namespace Nuwani;

class NetworkManager extends Singleton
{
        /**
         * Declares an array with all network info which can be stored, like
         * number of bots connected to it, on a per-server basis as well, etc.
         * 
         * @var array
         */
        
        private $m_aNetworkInfo;
        
        /**
         * This function will initialise the network manager, set it up with all
         * information as listed in the configuration file.
         * 
         * @param array $aNetworks Networks to be initialised right now.
         */
        
        public function Initialize ($aNetworks)
        {
                $this -> m_aNetworkInfo = array ();
                
                foreach ($aNetworks as $sName => $aServers)
                {
                        $this -> addServer ($sName, $aServers);
                }
        }
        
        /**
         * This function will add a network to the network manager, it can then
         * be used by the bots to connect to.
         * 
         * @param string $sName Name for the network to be added.
         * @param mixed $mServers String or array with server addresses.
         */
        
        public function addServer ($sName, $mServers)
        {
                if (!is_array ($mServers))
                {
                        $mServers = array ($mServers);
                }
                
                $this -> m_aNetworkInfo [$sName] = array 
                (
                        'Servers'       => array (),
                        'Supported'     => array ()
                );
                
                foreach ($mServers as $sServerInfo)
                {
                        if (strpos ($sServerInfo, ':') === false)
                        {
                                /** Add default port. **/
                                $sServerInfo .= ':6667';
                        }
                        
                        list ($sAddress, $nPort) = explode (':', $sServerInfo);
                        $aAddresses = gethostbynamel ($sAddress);
                        
                        if (!$aAddresses)
                        {
                                /** No IP(s) found, so it's of no use to us. **/
                                continue;
                        }
                        
                        /** Support for round robin addresses. **/
                        foreach ($aAddresses as $sResolvedAddress)
                        {
                                $this -> m_aNetworkInfo [$sName] ['Servers'] [] = array
                                (
                                        'IP'        => $sResolvedAddress,
                                        'Port'      => $nPort,
                                        'Count'     => 0
                                );
                        }
                }
                
                $this -> m_aNetworkInfo [$sName] ['Count'] = 0;
        }
        
        /**
         * The function which decide which server will be used for a certain bot
         * on a certain network, based on the load of the other servers.
         * 
         * @param string $sNetwork Network we have to get a server for.
         * @return array
         */
        
        public function getServer ($sNetwork)
        {
                if (!isset ($this -> m_aNetworkInfo [$sNetwork]))
                {
                        return false;
                }
                
                $aBotServer = array ('Count' => 999);
                foreach ($this -> m_aNetworkInfo [$sNetwork] ['Servers'] as $iIndex => $aServerInfo)
                {
                        if ($aBotServer ['Count'] > $aServerInfo ['Count'])
                        {
                                $aBotServer = array
                                (
                                        'Count'         => $aServerInfo ['Count'],
                                        'Info'          => $aServerInfo,
                                        'Index'         => $iIndex
                                );
                        }
                }
                
                $this -> m_aNetworkInfo [$sNetwork] ['Servers'] [$aBotServer ['Index']] ['Count'] ++;
                $this -> m_aNetworkInfo [$sNetwork] ['Count'] ++;
                
                return $aBotServer ['Info'];
        }
        
        /**
         * This function can be used to get a certain piece of information from
         * the network specified in the first parameter.
         * 
         * @param string $sNetwork Network to get the rule of.
         * @param string $sRuleName Name of the rule you wish to retrieve.
         * @return mixed
         */
        
        public function getSupportRule ($sNetwork, $sRuleName)
        {
                if (!isset ($this -> m_aNetworkInfo [$sNetwork]))
                {
                        return false;
                }
                
                if (!isset ($this -> m_aNetworkInfo [$sNetwork]['Supported'][$sRuleName]))
                {
                        return false;
                }
                
                return $this -> m_aNetworkInfo [$sNetwork]['Supported'][$sRuleName];
        }
        
        /**
         * IRC Servers send a series of messages informing the users about what
         * they're capable of, and basic public configuration. Convenient for
         * properly-working modules.
         * 
         * @param string $sNetwork Name of the network that we'll be parsing.
         * @param array $aInformation Array with all information about the server.
         */
        
        public function parseSupported ($sNetwork, $aInformation)
        {
                if (!isset ($this -> m_aNetworkInfo [$sNetwork]))
                {
                        return;
                }
                
                foreach ($aInformation as $sValue)
                {
                        if (substr ($sValue, 0, 1) == ':')
                        {
                                /** End of the server information. **/
                                break;
                        }
                        
                        if (strpos ($sValue, '=') !== false)
                        {
                                list ($sKey, $sValue) = explode ('=', $sValue, 2);
                                $this -> m_aNetworkInfo [$sNetwork] ['Supported'] [$sKey] = $sValue;
                        }
                        else
                        {
                                $this -> m_aNetworkInfo [$sNetwork] ['Supported'] [$sValue] = true;
                        }
                }
        }
};

?>
