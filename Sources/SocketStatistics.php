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
 * @version $Id: SocketStatistics.php 129 2010-12-26 11:37:28Z peter@lvp-media.com $
 * @package Nuwani
 */

namespace Nuwani;

/**
 * Statistics for a socket will be tracked by this class. More specifically, we'll keep track of the
 * number of incoming messages and bytes, which also allows us to calculate the I/O load the bot
 * will be under. This is convenient in managing buffering and load balancing over multiple bots.
 * 
 * @package Nuwani
 */
class SocketStatistics
{
        /**
         * The load accuracy defines how precise load tracking should occur. A snapshot of the load
         * will be stored each second, and when requesting the load we'll return an average over
         * this number of samples. A lower value equals more accurate load sampling.
         *
         * @var integer
         */
        
        const   STATISTICS_LOAD_ACCURACY        = 10;
        
        /**
         * Various methods allow us to retrieve information, and passing on these constants allow
         * you to define whether it should return incoming or outgoing statistics.
         * 
         * @var integer
         */
        
        const   STATISTICS_INCOMING             = 0;
        const   STATISTICS_OUTGOING             = 1;
        
        /**
         * The incoming statistics will be stored within this property. It's an array which contains
         * four statistics: messages, bytes, bytes in the last second and load snapshots.
         *
         * @var array
         */
        
        private $incoming;
        
        /**
         * Similary to the incoming statistical array, this one will store the outgoing statistics.
         * The fields will be identical: messages, bytes, bytes in the last second and snapshots.
         *
         * @var array
         */
        
        private $outgoing;
        
        /**
         * Considering we want accurate samples of the bot's load, we have to store the last time at
         * which it has been updated. This will be a microsecond accurate timestamp.
         *  
         * @var float
         */
        
        private $loadUpdate;
        
        /**
         * The constructor will initialize the properties with the statistical arrays and set the
         * initial value for the loadUpdate timer, allowing the update function to work correctly.
         */
        
        public function __construct ()
        {
                $this -> incoming = $this -> outgoing = array
                (
                        'messages'      => 0,
                        'bytes'         => 0,
                        
                        'load'          => array
                        (
                                'counter'       => 0,
                                'snapshots'     => array ()
                        )
                );
                
                $this -> loadUpdate = microtime (true);
        }
        
        /**
         * We have to create a new snapshot for both the incoming as the outgoing messages every
         * second, which this method will nicely do. The socket has to make sure that we'll be
         * invoked, otherwise load tracking will not work.
         */
        
        public function update ()
        {
                if ((microtime (true) - $this -> loadUpdate) < 1000)
                        return;
                
                $this -> incoming ['load']['snapshots'][] = $this -> incoming ['load']['counter'];
                $this -> outgoing ['load']['snapshots'][] = $this -> outgoing ['load']['counter'];
                
                if (count ($this -> incoming ['load']['snapshots']) > self :: STATISTICS_LOAD_ACCURACY)
                {
                        array_shift ($this -> incoming ['load']['snapshots']);
                }
                
                if (count ($this -> outgoing ['load']['snapshots']) > self :: STATISTICS_LOAD_ACCURACY)
                {
                        array_shift ($this -> outgoing ['load']['snapshots']);
                }
                
                $this -> incoming ['load']['counter'] = 0;
                $this -> outgoing ['load']['counter'] = 0;
                
                $this -> loadUpdate = microtime (true);
        }
        
        /**
         * Getting the average load of this socket may be done by invoking the load method, which
         * will return the average of the snapshots contained within the local array. The accuracy
         * of this method will be defined by the STATISTICS_LOAD_ACCURACY constant.
         *
         * @param integer $direction Do you want to retreive incoming or outgoing statistics?
         * @return float The average load over the past few seconds.
         */
        
        public function load ($direction = self :: STATISTICS_OUTGOING)
        {
                if ($direction == self :: STATISTICS_INCOMING)
                {
                        return ($this -> incoming ['load']['counter'] + array_sum ($this -> incoming ['load']['snapshots'])) / (1 + count ($this -> incoming ['load']['snapshots']));
                }
                
                return ($this -> outgoing ['load']['counter'] + array_sum ($this -> outgoing ['load']['snapshots'])) / (1 + count ($this -> outgoing ['load']['snapshots']));
        }
        
        /**
         * Pushing a message to record statistics from may be done by invoking this method. The two
         * parameters are both required because they're needed to record the statistics correctly.
         *
         * @param integer $direction Is this message being retreived or being distributed?
         * @param string $message The message which has to be recorded.
         */
        
        public function push ($direction, $message)
        {
                $messageLength = strlen ($message);
                
                if ($direction == self :: STATISTICS_INCOMING)
                {
                        $this -> incoming ['messages'] ++;
                        $this -> incoming ['bytes'] += $messageLength;
                        
                        $this -> incoming ['load']['counter'] += $messageLength;
                        return;
                }
                
                $this -> outgoing ['messages'] ++;
                $this -> outgoing ['bytes'] += $messageLength;
                
                $this -> outgoing ['load']['counter'] += $messageLength;
        }
        
        /**
         * Getting the statistics of this class, specifically the number of bytes and messages, can
         * be done by invoking this method. We'll return an array with both numbers.
         *
         * @param integer $direction Do you want to retreive incoming or outgoing statistics?
         * @return array An array with two entries, the number of messages and bytes.
         */
        
        public function statistics ($direction = self :: STATISTICS_OUTGOING)
        {
                if ($direction == self :: STATISTICS_INCOMING)
                {
                        return array
                        (
                                'messages'      => $this -> incoming ['messages'],
                                'bytes'         => $this -> incoming ['bytes']
                        );
                }
                
                return array
                (
                        'message'       => $this -> outgoing ['messages'],
                        'bytes'         => $this -> outgoing ['bytes']
                );
        }
}
