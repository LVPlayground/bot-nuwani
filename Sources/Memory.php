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
 * @version $Id: Memory.php 136 2011-02-23 01:23:09Z dik.grapendaal $
 * @package Nuwani
 */
 
namespace Nuwani;

class Memory
{
        /**
         * This property contains various statistics about our memory-cleanup
         * operations. This utilizes a fairly new, not-documented PHP 5.3
         * feature.
         * 
         * @var array
         */
        
        private static $s_pStatistics;
        
        /**
         * A property which defines the time the last garbage collection round
         * took place, useful seeing we only want to do this once every five
         * seconds.
         * 
         * @var integer
         */
        
        private static $s_nLastCollect;
        
        /**
         * In the constructor we'll simply initialise the earlier two properties,
         * respectivily with an array and the current timestamp.
         */
        
        public static function Initialize ()
        {
                self :: $s_pStatistics = new \ stdClass ();
                self :: $s_pStatistics -> Memory = 0;
                self :: $s_pStatistics -> Cycles = 0;
                
                self :: $s_nLastCollect = time ();
                
                if (!gc_enabled ())
                {
                        gc_enable ();
                }
        }
        
        /**
         * The process function will determain whether we have to do a garbage
         * collecting round, and if so, process the memory rounds.
         */
        
        public static function Process ()
        {
                if (time () - self :: $s_nLastCollect >= 5)
                {
                        $nStart = memory_get_usage ();
                        
                        gc_collect_cycles ();
                        
                        $nDifference = (memory_get_usage () - $nStart);
                        self :: $s_pStatistics -> Memory += ($nDifference > 0) ? $nDifference : 0;
                        self :: $s_pStatistics -> Cycles ++;
                        self :: $s_nLastCollect = time ();
                }
        }
        
        /**
         * This method has been deprecated, use the getGatheredMemory and 
         * getProcessCycles methods instead.
         * 
         * @return array
         */
        
        public static function getStatistics ()
        {
                trigger_error ('Memory :: getStatistics () has been deprecated. Use Memory :: getGatheredMemory () and Memory :: getProcessCycles () instead.', E_USER_DEPRECATED);
                return array
                (
                        'Memory'        => self :: $s_pStatistics -> Memory,
                        'Cycles'        => self :: $s_pStatistics -> Cycles,
                        'Elements'      => -1
                );
        }
        
        /**
         * Returns the amount of freed memory since start up.
         * 
         * @return integer
         */
        
        public static function getGatheredMemory ()
        {
                return self :: $s_pStatistics -> Memory;
        }
        
        /**
         * Returns the number of cycles we've already had since start up.
         * 
         * @return integer
         */
        
        public static function getProcessCycles ()
        {
                return self :: $s_pStatistics -> Cycles;
        }
}

?>
