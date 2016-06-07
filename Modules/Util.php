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
 * @package Util Module
 * @author Peter Beverloo <peter@lvp-media.com>
 * @author Dik Grapendaal <dik@sa-mp.nl>
 * @see http://nuwani.googlecode.com
 */

class Util extends ModuleBase
{
        /**
         * This static method allows you to extract a certain range of elements
         * out of an array, and return it as a string. Very similar to PHP's
         * implode() function, only this method allows you to specify a range in
         * the input array.
         * 
         * @param array $aInput The input array.
         * @param string $sSep The string to put between the elements.
         * @param integer iStart The index of the element of where to start in the array.
         * @param integer iEnd The index of the element of where to end in the array.
         * @return string
         */
        
        public static function getPieces ($aInput, $sSep = ' ', $iStart = 0, $iEnd = null)
        {
                return implode ($sSep, array_slice ($aInput, $iStart, $iEnd));
        }
        
        /**
         * Converts the input number of seconds to a human readable string. This can
         * either be a full featured string or a short one with only the first letters
         * of the units.
         * 
         * @param integer $nSeconds The number of seconds to format.
         * @param boolean $bShort Do you want a shorter notation?
         * @return string
         */
        
        public static function formatTime ($nSeconds, $bShort = false)
        {
                $aModulos = array (31556926, 604800, 86400, 3600, 60, 1);
                
                /** It is possible to define your own language. **/
                $aLanguage = array
                (
                        's'     => array ('year', 'week', 'day', 'hour', 'minute', 'second'),
                        'p'     => array ('years', 'weeks', 'days', 'hours', 'minutes', 'seconds'),
                        'and'   => 'and',
                );
                if ($bShort)
                {
                        $cFirstLetter = function ($sValue)
                        {
                                return $sValue [0];
                        };
                        
                        $aLanguage ['s'] = array_map ($cFirstLetter, $aLanguage ['s']);
                        $aLanguage ['p'] = array_map ($cFirstLetter, $aLanguage ['p']);
                        $aLanguage ['and'] = '';
                }
                
                /** Extract the time units. **/
                $sTime = '';
                foreach ($aModulos as $i => $nModulo)
                {
                        if ($nSeconds == 0)
                        {
                                break;
                        }
                        
                        $nResult = floor ($nSeconds / $nModulo);
                        if ($nResult > 0)
                        {
                                // Remove the time unit from the total.
                                $nSeconds %= $nModulo;
                                
                                if (strlen ($sTime) != 0)
                                {
                                        // This is not the first element.
                                        if ($bShort)
                                        {
                                                $sTime .= ' ';
                                        }
                                        else
                                        {
                                                if (!isset ($aModulos [$i + 1]) || $aModulos [$i + 1] > $nSeconds)
                                                {
                                                        // This is the last loop, add 'and'.
                                                        $sTime .= ' ' . $aLanguage ['and'] . ' ';
                                                }
                                                else
                                                {
                                                        // More is coming, add a comma.
                                                        $sTime .= ', ';
                                                }
                                        }
                                }
                                
                                $sTime .= $nResult;
                                if (!$bShort)
                                {
                                        $sTime .= ' ';
                                }
                                
                                if ($nResult == 1)
                                {
                                        // Use single.
                                        $sTime .= $aLanguage ['s'][$i];
                                }
                                else
                                {
                                        // Use plural.
                                        $sTime .= $aLanguage ['p'][$i];
                                }
                        }
                }
                
                if (strlen ($sTime) == 0)
                {
                        /** Still empty? Make something nice. **/
                        $sTime = '0' . ($bShort ? '' : ' ') . $aLanguage ['p'] [count ($aLanguage ['p']) - 1];
                }
                
                return $sTime;
        }
        
        /**
         * Formats a given size to a human readable string. It allows you to
         * specify the number of decimals, as well as the unit the size
         * currently is in. This is for optimum performance.
         * 
         * @param integer $nSize The size to format.
         * @param integer $nDecimals The number of decimals to show.
         * @param string $sCurrentUnit The symbol of the unit nSize currently is in.
         * @return string
         */
        
        public static function formatSize ($nSize, $nDecimals = 2, $sCurrentUnit = null)
        {
                $aSizes = array ('', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y');
                
                $i = 0;
                if ($sCurrentUnit != null)
                {
                        $i = (int) array_search (strtoupper ($sCurrentUnit [0]), $aSizes);
                }
                
                // Keep dividing while we have units and more than 1024 of them.
                while ($nSize >= 1024 && $i < 8)
                {
                        $i++;
                        $nSize /= 1024;
                }
                
                return round ($nSize, $nDecimals) . ' ' . $aSizes [$i] . 'B';
        }
        
        /**
         * This function removes all IRC formatting from the given message.
         * 
         * @param string $sMessage The message to strip the format of.
         * @return string
         */
        
        public static function stripFormat ($sMessage)
        {
                return preg_replace
                (
                        '/(' .
                        ModuleBase :: BOLD . '|' .
                        ModuleBase :: COLOUR . '\d{0,2}(?:,\d{1,2}|)|' .
                        ModuleBase :: CLEAR . '|' .
                        ModuleBase :: INVERSE . '|' .
                        ModuleBase :: ITALIC . '|' .
                        ModuleBase :: UNDERLINE . ')/',
                        '',
                        $sMessage
                );
        }
        
        /**
         * This function will split the message into usable pieces for commands,
         * a trigger, the string with all parameters and all parameters into a
         * by spaces split array.
         * 
         * @param string $sMessage The message to be parsed.
         * @return array
         */
        
        public static function parseMessage ($sMessage)
        {
                if (strpos ($sMessage, ' ') !== false)
                {
                        list ($sTrigger, $sParams) = preg_split ('/\s+/', $sMessage, 2);
                        $sParams = trim ($sParams);
                        $aParams = preg_split ('/\s+/', $sParams);
                }
                else
                {
                        $sTrigger = $sMessage;
                        $sParams  = null;
                        $aParams  = array ();
                }
                
                return array ($sTrigger, $sParams, $aParams);
        }
        
        /**
         * This function calculates the adler32 hash of the given string. Very
         * useful for filenames, as this only outputs a number.
         * 
         * @param string $sInput The input to calculate the hash of.
         * @return integer
         */
        
        public static function adler32 ($sInput)
        {
                $s1 = 1;
                $s2 = $s4 = 0;
                
                for ($i = 0 ; $i < strlen ($sInput) ; $i++)
                {
                        $s1 = ($s1 + ord ($sInput [$i])) % 65521;
                        $s2 = ($s2 + $s1) % 65521;
                }
                $s3 = decbin ($s2);
                
                for ($i = 0; $i < (64 - strlen ($s3)); $i++)
                {
                        $s3 = "0" . $s3;
                }
                
                for ($i = 0; $i < 16; $i++)
                {
                        $s3 = substr ($s3 . "0", 1);
                }
                
                for ($i = 0; $i < strlen ($s3); $i++)
                {
                        $s4 = $s4 + $s3 [$i] * pow (2, strlen ($s3) - $i - 1);
                }
                
                return $s4 + $s1;
        }
}

/**
 * Backwards compatibility.
 */

class Func extends Util {}
?>
