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

class TargetChannel {

        // List of keys allowed in the configuration.
        private static $allowedKeys = array('rights', 'managers', 'management', 'crew', 'development', 'vip', 'public');

        // Internal representation of all known IRC channels.
        private static $channelConfiguration;

        public static function Initialize($messageFeeds, $channelConfiguration) {
                self::$channelConfiguration = array();

                foreach ($channelConfiguration as $key => $channels) {
                        if (in_array($key, self::$allowedKeys)) {
                                if (!is_array($channels)) {
                                        $channels = array($channels);
                                }

                                self::$channelConfiguration[$key] = array_map('strtolower', $channels);
                        } else {
                                echo 'Channels "' . implode(', ', $channels) . '" skipped because type "' . $key . '" is invalid.';
                        }
                }

                foreach ($messageFeeds as $feed)
                        self::$channelConfiguration['echo'][$feed['name']] = strtolower($feed['channel']);
        }

        public static function getChannels() {
                $keys = func_get_args();
                // Make more sense of an array inside an array.
                if (is_array($keys) && count($keys) == 1 && is_array($keys[0])) {
                    $keys = $keys[0];
                }
                $result = array();
                foreach ($keys as $key) {
                        $result = array_merge($result, self::$channelConfiguration[$key]);
                }
                if (count($result) == 1) {
                        return $result[0];
                }
                return $result;
        }

        private static function isChannel($key, $channel) {
                return in_array(strtolower($channel), self::$channelConfiguration[$key]);
        }

        private static function isEchoChannel($key, $channel) {
                return strtolower($channel) == self::$channelConfiguration['echo'][$key];
        }

        /*
         * Public utility methods.
         */

        public static function isManagementChannel($channel) {
                return self::isChannel('managers', $channel) || self::isChannel('management', $channel);
        }

        public static function isCrewChannel($channel) {
                return self::isChannel('crew', $channel);
        }

        public static function isDevelopmentChannel($channel) {
                return self::isChannel('development', $channel) || self::isEchoChannel('developer', $channel);;
        }

        public static function isVipChannel($channel) {
                return self::isChannel('vip', $channel);
        }

        public static function isPublicEchoChannel($channel) {
                return self::isEchoChannel('public', $channel);
        }

        public static function isDevelopmentEchoChannel($channel) {
                return self::isEchoChannel('developer', $channel);
        }

        /*
        * Utility methods for retrieving standard sets of IRC channels.
        */

        public static function crewAnnouncementChannels() {
                return self::getChannels('crew', 'managers');
        }

        public static function chatChannels() {
                return self::getChannels('managers', 'management', 'development', 'crew', 'public');
        }

        public static function crewChannel() {
                return self::$channelConfiguration['crew'];
        }

        public static function echoChannel() {
                return self::$channelConfiguration['echo']['public'];
        }

        public static function developmentEchoChannel() {
                return self::$channelConfiguration['echo']['developer'];
        }

        public static function developmentChannel() {
                return self::getChannels('development');
        }

        public static function managementChannel() {
                return self::getChannels('management');
        }

        public static function managersChannel() {
                return self::getChannels('managers');
        }

        public static function vipChannel() {
                return self::getChannels('vip');
        }
}
