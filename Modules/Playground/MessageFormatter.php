<?php
/**
 * Copyright (c) 2006-2013 Las Venturas Playground
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
 */

namespace Playground;

class MessageFormatter {
    private static $m_filename;
    private static $m_format;
    
    public static function Initialize($filename) {
        self::$m_filename = $filename;
        self::Reload();
    }
    
    // Reloads the formatting rules to get the latest conventions.
    public static function Reload() {
        include (self::$m_filename);
        self::$m_format = $format;
    }
    
    // Formats a message according to the formatter rules.
    public static function Format($message, $defaultChannel, $eventListener) {
        $destination = $defaultChannel;
        $prefix = '';
        $messageFeed = null;

        foreach (self::$m_format as $rule) {
            if (preg_match($rule['match'], $message, $matches) != 1){
                continue;
            }
            
            // We found the right rule. Now apply it in the way the rule desires. We support
            // various kinds of rules to ensure the flexibility we unfortunately require.
            if (isset($rule['format'])) {
                $message = preg_replace($rule['match'], $rule['format'], $message);
            }
            else if (isset($rule['function'])) {
                $message = preg_replace_callback($rule['match'], $rule['function'], $message);
            }
            
            // See if we need to send the result to another feed.
            if (isset($rule['message-feed'])) {
                $messageFeed = $rule['message-feed'];
                return self::createMessageDefinition($destination, $prefix, $message, $messageFeed);
            }

            // Call the event associated with this rule if there is any.
            if (isset($rule['event'])) {
                call_user_func_array(array($eventListener, $rule['event']), array($matches));
            }

            // Now apply the modifiers which we make available for the rules.
            if (isset($rule['prefix'])) {
                $prefix = $rule['prefix'];
            }
            
            if (isset($rule['destination'])) {
                $destination = $rule['destination'];
            }
            
			// Should we block the message for a certain destination?
			if (isset($rule['block-destination']) && self::isChannel($rule['block-destination'], $destination)) {
				return null;
            }
			
            // We've applied the necessary styles to the rule -- break out of the loop.
            return self::createMessageDefinition($destination, $prefix, $message, $messageFeed);
        }
        
        // No rule was matched, don't output the message.
        return null;
    }

    private static function createMessageDefinition($destination, $prefix, $message, $messageFeed) {
        return array(
            'destination'  => $destination,
            'prefix'       => $prefix,
            'message'      => $message,
            'message-feed' => $messageFeed
        );
    }

    private static function isChannel($needle, $haystack) {
        // Both $needle and $haystack can be a string or an array... but there should be an easier way to do this.
        if (is_array($haystack)) {
            if (is_array($needle)) {
                return count(array_intersect($needle, $haystack)) > 0;
            }
            return in_array($needle, $haystack);
        } else {
            if (is_array($needle)) {
                return in_array($haystack, $needle);
            }

            return $needle == $haystack;
        }
    }
};

