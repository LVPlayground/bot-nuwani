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
 * @version $Id: ModuleManager.php 136 2011-02-23 01:23:09Z dik.grapendaal $
 * @package Nuwani
 */
 
namespace Nuwani;
use \ ModuleBase;

class ModuleManager extends Singleton implements \ ArrayAccess, \ SeekableIterator, \ Countable
{
        /**
         * The directory where all modules should be placed in, will be specified
         * using this little constant right here. This is useful for modules wanting
         * to have a look in the modules directory themselves, without hardcoding
         * the actual directory name.
         */
        
        const MODULE_DIRECTORY  = 'Modules';
        
        /**
         * This constant can be returned by callback functions in modules, which
         * will indicate that the callback loop should be stopped immediatly.
         * 
         * @var integer
         */
        
        const   FINISHED        = -1;

        /**
         * An array of the modules which have been loaded into the Nuwani system,
         * uses their names as the array index.
         * 
         * @var array
         */
        
        private $m_aModules;
        
        /**
         * The constructor will initialise the module-settings and auto-load all
         * modules that have to be loaded, by checking out the module directory.
         */
        
        protected function __construct ()
        {
                $this -> m_aModules = array ();
                $pFileList = new \ DirectoryIterator (self :: MODULE_DIRECTORY);
                
                foreach ($pFileList as $pFileInfo)
                {
                        $sFilename = $pFileInfo -> getFilename ();
                        if ($pFileInfo -> isDot () || $sFilename [0] == '.')
                        {
                                /** Ignore the hidden and the . and .. directories. **/
                                continue;
                        }
                        
                        if ($pFileInfo -> isFile () && substr ($sFilename, -4) == '.php')
                        {
                                $sFilename = substr ($pFileInfo -> getFilename (), 0, -4);
                        }
                        else if (!$pFileInfo -> isDir ())
                        {
                                /** Can't be interesting if it's not a directory nor a PHP file. **/
                                continue;
                        }
                        
                        try
                        {
                                echo 'Loading the ' . $sFilename . ' module... ';
                                if ($this -> loadModule ($sFilename, false))
                                {
                                        echo 'Done.';
                                }
                                else
                                {
                                        echo 'Failed.';
                                }
                        }
                        catch (Exception $pException)
                        {
                                echo 'Failed. ' . $pException -> getMessage ();
                        }
                        
                        echo PHP_EOL;
                }
                
                $this -> prioritize ();
        }
        
        /**
         * This function will allow you to load a module into the manager, so
         * all callbacks and things will be forwarded properly.
         * 
         * @param string $sName Name of the module you wish to load.
         * @param boolean $bReorder Re-organize all modules based on priority.
         * @throws Exception When the module could not be loaded.
         * @return boolean
         */
        
        public function loadModule ($sName, $bReorder = true)
        {
                if (isset ($this -> m_aModules [$sName]))
                {
                        /** TODO: Should we keep this or return false? **/
                        $this -> unloadModule ($sName);
                }
                
                $sPath = self :: MODULE_DIRECTORY . '/' . $sName;
                if (file_exists ($sPath) && is_dir ($sPath))
                {
                        $sPath .= '/Module.php';
                }
                else
                {
                        $sPath .= '.php';
                }
                
                if (file_exists ($sPath))
                {
                        if (class_exists ($sName))
                        {
                                /** This module was already loaded, just re-instantiate it. **/
                                if (function_exists ('runkit_import'))
                                {
                                        /** Overwrite it using runkit when available. **/
                                        runkit_import ($sPath, RUNKIT_IMPORT_OVERRIDE | RUNKIT_IMPORT_CLASSES);
                                }
                        }
                        else
                        {
                                /** Try to define the expected class by including the file we expect it to be in. **/
                                include_once $sPath;
                                
                                if (!class_exists ($sName))
                                {
                                        throw new \ Exception ('Module file does not define a class with the name ' . $sName . '.');
                                }
                        }
                }
                else
                {
                        throw new \ Exception ('There was no file found which should contain the module ' . $sName . '.');
                }
                
                try
                {
                        $this -> m_aModules [$sName] = array
                        (
                                'Instance'    => new $sName (),
                                'Started'     => time (),
                                'Methods'     => array ()
                        );
                }
                catch (\ Exception $pException)
                {
                        throw new \ Exception ('Exception occurred during instantiation of module ' . $sName . '.', 0, $pException);
                }
                
                if (! ($this -> m_aModules [$sName] ['Instance'] instanceof ModuleBase))
                {
                        unset ($this -> m_aModules [$sName]);
                        
                        throw new \ Exception ('The class ' . $sName . ' is not a module.');
                }
                
                $pClassObj = new \ ReflectionClass ($sName);
                foreach ($pClassObj -> getMethods () as $pMethodObj)
                {
                        $this -> m_aModules [$sName] ['Methods'] [$pMethodObj -> getName ()] = $pMethodObj;
                }
                unset ($pClassObj);
                
                if ($bReorder == true)
                {
                        /** Re-order the modules. **/
                        $this -> prioritize ();
                }
                
                /** Callback for other modules to inform them that a module has been loaded. **/
                $this -> onModuleLoad ($this -> m_aModules [$sName] ['Instance']);
                
                /** If this module has this callback as well, pass all other modules through it. **/
                if (isset ($this -> m_aModules [$sName] ['Methods'] ['onModuleLoad']))
                {
                        foreach ($this -> m_aModules as $sModuleName => $pModule)
                        {
                                if ($sModuleName == $sName)
                                {
                                        /** Already done this one. **/
                                        continue;
                                }
                                
                                $this -> m_aModules [$sName] ['Instance'] -> onModuleLoad ($pModule ['Instance']);
                        }
                }
                
                return true;
        }
        
        /**
         * The prioritize function will order all modules based on their priority.
         * All calculations are done all-over again, so it can be somewhat costy
         * on the performance. Use the second parameter of loadModule properly!
         * 
         * @return boolean
         */
        
        private function prioritize ()
        {
                $aPriorityQueue = Configuration :: getInstance () -> get ('PriorityQueue');
                if (count ($aPriorityQueue) == 0)
                {
                        return false;
                }
                
                $aModuleList = array
                (
                        'Prioritized'   => array (),
                        'Normal'        => array ()
                );
                
                /** Determine which modules are prioritized. **/
                foreach ($this -> m_aModules as $sName => $pModule)
                {
                        $nPriority = array_search ($sName, $aPriorityQueue, true);
                        if ($nPriority !== false)
                        {
                                $aModuleList ['Prioritized'] [$nPriority] = $pModule;
                        }
                        else
                        {
                                $aModuleList ['Normal'] [$sName] = $pModule;
                        }
                }
                
                /** Now sort both arrays, and merge them into one array. **/
                ksort ($aModuleList ['Prioritized']);
                ksort ($aModuleList ['Normal']);
                
                $this -> m_aModules = array ();
                foreach ($aModuleList ['Prioritized'] as $pModule)
                {
                        $this -> m_aModules [get_class ($pModule ['Instance'])] = $pModule;
                }
                
                foreach ($aModuleList ['Normal'] as $sName => $pModule)
                {
                        $this -> m_aModules [$sName] = $pModule;
                }
                
                return true;
        }
        
        /**
         * This function destroys a class and throws it out of our internal 
         * module-array, so it won't be used any longer.
         * 
         * @param string $sName Name of the module that should be unloaded.
         * @return boolean
         */
        
        public function unloadModule ($sName)
        {
                if (!isset ($this -> m_aModules [$sName]))
                {
                        return false;
                }
                
                /** Callback for other modules (and this one). **/
                $this -> onModuleUnload ($this -> m_aModules [$sName]['Instance']);
                
                unset ($this -> m_aModules [$sName]);
                
                return true;
        }
        
        /**
         * This function easily reloads a module using the name of it. It calls
         * the two internal functions load- and unloadModule.
         * 
         * @param string $sName Which module would you like to reload?
         * @return boolean
         */ 
        
        public function reloadModule ($sName)
        {
                return $this -> unloadModule ($sName) && $this -> loadModule ($sName);
        }

        /**
         * A simply method returning the number of modules which currently have
         * been loaded by this manager, counting our internal array.
         * 
         * @return integer
         */
        
        public function count ()
        {
                return count ($this -> m_aModules);
        }
                
        /**
         * This function calls a certain function in all of the modules, so
         * it will automatically be forwarded. 
         * 
         * @param string $sFunction Name of the function being called.
         * @param array $aParameters Parameters being passed along.
         * @return boolean
         */
        
        public function __call ($sFunction, $aParameters)
        {
                foreach ($this -> m_aModules as $sKey => $aModuleInfo)
                {
                        if (isset ($aModuleInfo ['Methods'][$sFunction]))
                        {
                                $nReturnValue = $aModuleInfo ['Methods'][$sFunction] -> invokeArgs 
                                (
                                        $aModuleInfo ['Instance'], $aParameters
                                );
                                
                                if ($nReturnValue === self :: FINISHED)
                                {
                                        return true;
                                }
                        }
                }
                
                return false;
        }
        
        // -------------------------------------------------------------------//
        // Region: ArrayAccess                                                //
        // -------------------------------------------------------------------//
        
        /**
         * The function that will be called when a certain module is requested
         * from this handler. There is a check done for existance.
         * 
         * @param string $sKey Key of the entry that you want to receive.
         * @return mixed
         */
        
        public function offsetGet ($sKey)
        {
                if (isset ($this -> m_aModules [$sKey]))
                {
                        return $this -> m_aModules [$sKey] ['Instance'];
                }
                
                return false;
        }
        
        /**
         * Checks whether a key with this name exists, and if so, returns
         * true, otherwise a somewhat more negative boolean gets returned.
         * 
         * @param string $sKey Key of the entry that you want to check.
         * @return boolean
         */
        
        public function offsetExists ($sKey)
        {
                return isset ($this -> m_aModules [$sKey]);
        }
        
        /**
         * This function can be used to set associate a value with a certain key
         * in our internal array, however, that's disabled seeing we're locking
         * values.
         * 
         * @param string $sKey Key of the entry that you want to set.
         * @param mixed $mValue Value to assign to the key.
         * @return null
         */
        
        public function offsetSet ($sKey, $mValue)
        {
                return ;
        }
        
        /**
         * This function will get called as soon as unset() gets called on the 
         * Modules instance, which is not properly supported either.
         * 
         * @param string $sKey Key of the entry that you want to unset.
         * @return null
         */
        
        public function offsetUnset ($sKey)
        {
                return ;
        }
        
        // -------------------------------------------------------------------//
        // Region: SeekableIterator                                           //
        // -------------------------------------------------------------------//
        
        /**
         * This function returns the current active item in the module list,
         * defined by a key and the associated priority.
         * 
         * @return ModuleBase
         */
        
        public function current ()
        {
                $aCurrent = current ($this -> m_aModules);
                return $aCurrent ['Instance'];
        }
        
        /**
         * Returns the key of the currently active item in the module info array,
         * quite simple using the function with exactly the same name. This will
         * return the name of the currently selected module.
         * 
         * @return string
         */
        
        public function key ()
        {
                return key ($this -> m_aModules);
        }
        
        /**
         * Advanced the module-manager's pointer to the next entry in the array,
         * returning the value whatever that might be.
         * 
         * @return ModuleBase
         */
        
        public function next ()
        {
                $aNext = next ($this -> m_aModules);
                return $aNext ['Instance'];
        }
        
        /**
         * Rewinds the array to the absolute beginning, so iterating over it can
         * start all over again.
         * 
         * @return ModuleBase
         */
        
        public function rewind ()
        {
                return reset ($this -> m_aModules);
        }
        
        /**
         * Determaines whether the current array index is a valid one, and not
         * "beyond the array", which surely is possible with arrays.
         * 
         * @return boolean
         */
        
        public function valid ()
        {
                return current ($this -> m_aModules) !== false;
        }
        
        /**
         * The seek function "seeks" to a certain position within the module
         * array, so we can skip the absolutely uninteresting parts.
         * 
         * @param mixed $mIndex Index that we wish to seek to.
         * @throws OutOfBoundsException When the position cannot be seeked to.
         */
        
        public function seek ($mIndex)
        {
                reset ($this -> m_aModules);
                $nPosition = 0;
                
                while ($nPosition < $mIndex && (current ($this -> m_aModules) !== false))
                {
                        next ($this -> m_aModules);
                        $nPosition ++;
                }
                
                if (current ($this -> m_aModules) === false)
                {
                        throw new \ OutOfBoundsException ('Cannot seek to position ' . $mIndex);
                }
        }

}

?>
