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
 * @version $Id: Configuration.php 136 2011-02-23 01:23:09Z dik.grapendaal $
 * @package Nuwani
 */
 
namespace Nuwani;

class Configuration extends Singleton
{
        /**
         * The configuration will be stored in this array, after we pull it from
         * the global context, as it is defined in config.php ($aConfiguration).
         * 
         * @var array
         */
        
        private $m_aConfiguration;
        
        /**
         * This function will register the configuration array with this class,
         * making it available for all bot systems to use as they like.
         * 
         * @param array $aConfiguration Configuration you wish to register.
         */
        
        public function register ($aConfiguration)
        {
                $this -> m_aConfiguration = $aConfiguration ;
        }
        
        /**
         * This function will return an array with the configuration options
         * associated with the key as specified in the parameter.
         * 
         * @param string $sKey Key of the configuration item you wish to retrieve.
         * @return array
         */
        
        public function get ($sKey)
        {
                if (isset ($this -> m_aConfiguration [$sKey]))
                {
                        return $this -> m_aConfiguration [$sKey];
                }
                
                return array ();
        }
};

?>
