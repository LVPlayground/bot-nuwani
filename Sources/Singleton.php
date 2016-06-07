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
 * @version $Id: Singleton.php 136 2011-02-23 01:23:09Z dik.grapendaal $
 * @package Nuwani
 */
 
namespace Nuwani;

abstract class Singleton
{
        /**
         * This property will contain the active instances of the singleton'ned
         * classes, making sure there only is one of each active at any time.
         * 
         * @var array
         */

        private static $m_aClassInstances;

        /**
         * This function is the constructor of the class which inherits us. This
         * is done to force the private-visibility of the class' constructor
         * function.
         */
        
        private function __construct ()
        {
        }

        /**
         * This function will get a new instance of the class which inherits us,
         * or return the active one if it already existed.
         * 
         * @param Object $pInstance Allows one to specify a specific instance for that class.
         * @return Object
         */
        
        public static final function getInstance ($pInstance = null)
        {
                $sCallee = get_called_class ();
                if (!isset (self :: $m_aClassInstances [$sCallee]))
                {
                        if ($pInstance != null)
                        {
                                self :: $m_aClassInstances [$sCallee] = $pInstance;
                        }
                        else
                        {
                                self :: $m_aClassInstances [$sCallee] = new $sCallee ();
                        }
                }
                
                return self :: $m_aClassInstances [$sCallee] ;
        }

        /**
         * Cloning a singleton'ned class is not allowed, seeing it would create
         * another instance, which evidently is not allowed.
         * 
         * @throws Exception When this method is invoked.
         */
        
        public final function __clone ()
        {
                throw new \ Exception ('Cannot create a new instance of class "' . get_called_class () . '".');
        }
        
        /**
         * This function normally allows deserialisation of classes which
         * already existed (e.g. unserialize(serialize($class));), and thus
         * creating another copy. We don't allow this either.
         * 
         * @throws Exception When this method is invoked.
         */
        
        public final function __wakeup ()
        {
                throw new \ Exception ('Cannot unserialize the class "' . get_called_class () . '".');
        }
};

?>
