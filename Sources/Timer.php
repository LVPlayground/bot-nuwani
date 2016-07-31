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
 * @version $Id: Timer.php 136 2011-02-23 01:23:09Z dik.grapendaal $
 * @package Nuwani
 */

namespace Nuwani;

/**
 * Considering IRC bots generally are long-running, having the ability to set timers which will fire
 * after certain amounts of time certainly is convenient. This class implements that functionality,
 * allowing the bot's core or the modules to execute (public) methods or (anonymous?) functions.
 *
 * There are two primary kinds of timers which we will be handling. The first kind, "timeouts", are
 * functions which will be invoked only once after either a certain amount of time defined in
 * milliseconds, or at a certain time defined by a UNIX timestamp.
 *
 * The second kind of timer are called "intervals", and, as you might be expecting from its name,
 * will be invoked automatically once the interval has passed. At that time the timer will re-set
 * itself, queuing another invocation of the function.
 *
 * @package Nuwani
 */
class Timer
{
        /**
         * The types of timers which we are able to execute will internally be handled as one of two
         * following constants. The system defaults to timeouts, since functions which will only be
         * invoked once are usually less of a nuisance than the opposite scenario.
         *
         * @var integer
         */

        const   TIMEOUT            = 0;
        const   INTERVAL           = 1;

        /**
         * The running timers will be contained within this property. Each entry in this array will
         * have the following information associated with itself:
         *
         * 'callback'   The method, function or Closure which has to be invoked.
         * 'interval'   Interval, in seconds, at which the timer should execute.
         * 'type'       Type of timer (a run-once timeout or a repeating interval).
         * 'run_at'     UNIX Timestamp at which the next invocation should occur.
         *
         * Registering new timers will be done by the create() method, whereas removing timers will
         * be handled by the destroy() method.
         *
         * @var array
         */

        private static $timers = array ();

        /**
         * We store the last handed out timer Id as a property, considering this ensures that there
         * won't be any double Ids. The number will be incremented within the create method.
         *
         * @var integer
         */

        private static $timerId = 0;

        /**
         * Creating a new timer has to be done by invoking this method. It expects a number of
         * parameters to properly set up the timer. Only the callback itself is required, in which
         * case it will be executing during the next processing loop of the timers.
         *
         * @param Callback $callback The function, method or Closure which should be invoked.
         * @param integer $interval Interval in milliseconds or time at which we should invocate.
         * @param integer $type The type of timer which should be created.
         * @return integer Id of the timer which has been registered.
         */

        public static function create ($callback, $interval = 0, $type = self :: TIMEOUT)
        {
                if (!is_callable ($callback))
                {
                        throw new \ Exception ('No timer could be created: the callback is not executable.');
                        return false;
                }

                $nextInvocation = $interval;
                if ($interval < time ())
                {
                        $nextInvocation = microtime (true) + ($interval / 1000);
                }

                self :: $timers [++ self :: $timerId] = array
                (
                        'callback'      => $callback,
                        'interval'      => $interval,
                        'type'          => $type,

                        'run_at'        => $nextInvocation
                );

                return self :: $timerId;
        }

        /**
         * Processing all the timers will be done via the bot's main loop, during each tick within
         * the bot's system. We'll iterate over all available timers and execute them if they're
         * due. Iterators will be re-timed and timeouts will be destroyed.
         *
         * If the callback a timer invokes, for whatever reason, triggers an exception, the timer
         * will be destroyed and won't execute again.
         */

        public static function process ()
        {
                $currentTime = microtime (true);
                foreach (self :: $timers as $timerId => & $timerInfo)
                {
                        if ($timerInfo ['run_at'] > $currentTime)
                                continue;

                        try
                        {
                                call_user_func ($timerInfo ['callback']);

                                if ($timerInfo ['type'] === self :: TIMEOUT)
                                {
                                        self :: destroy ($timerId);
                                        continue;
                                }

                                $timerInfo ['run_at'] = microtime (true) + ($timerInfo ['interval'] / 1000);

                        }
                        catch (\ Exception $e)
                        {
                                self :: destroy ($timerId);
                        }
                }
        }

        /**
         * Destroying a timer may be done by invoking the destroy method. We'll remove the timer
         * from our local array, which will cause the process method to ignore it altogether.
         *
         * @param integer $timerId Id of the timer which should be destroyed.
         * @return boolean Were we properly able to destroy the timer?
         */

        public static function destroy ($timerId)
        {
                if (is_numeric ($timerId) && isset (self :: $timers [$timerId]))
                {
                        unset (self :: $timers [$timerId]);
                        return true;
                }

                return false;
        }
}
