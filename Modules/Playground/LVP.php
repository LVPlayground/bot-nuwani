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

require_once __DIR__ . '/LVP/Database.php';
require_once __DIR__ . '/LVP/IngameState.php';
require_once __DIR__ . '/LVP/Profile.php';
require_once __DIR__ . '/LVP/RemoteCommand.php';

class LVP {
    // Pieces of information that can be retrieved by getServerInformation().
    const GeneralInformation = 'i';
    const ServerRuleInformation = 'r';
    const PlayerInformation = 'd';
    
    // Number of days players need to wait between their nickname changes.
    const DaysBetweenNicknameChanges = 3;

    public static function setDatabaseInformation($hostname, $username, $password, $database) {
        Playground \ Database::setDatabaseInformation($hostname, $username, $password, $database);
    }
    public static function setPersistentInstance(MySQLi $instance) {
        Playground \ Database::setPersistentInstance($instance);
    }
    public static function setPasswordHashKey($key) {
        Playground \ Profile::setPasswordHashKey($key);
    }

    public static function findProfileById($userId) {
        return Playground \ Profile::findByUserId($userId);
    }
    public static function findProfileByNickname($nickname) {
        return Playground \ Profile::findByNickname($nickname);
    }
    
    public static function onlinePlayers() {
        return Playground \ IngameState::onlinePlayers();
    }
    public static function getOnlinePlayerById($id) {
        return Playground \ IngameState::getOnlinePlayerById($id);
    }
    public static function findOnlinePlayersByPartialNickname($nickname) {
        return Playground \ IngameState::findOnlinePlayersByPartialNickname($nickname);
    }
    
    public static function setRemoteCommandInformation($hostname, $port, $password) {
        Playground \ RemoteCommand::setRemoteCommandInformation($hostname, $port, $password);
    }
    public static function getServerInformation($informationIdentifier) {
        return Playground \ RemoteCommand::getServerInformation($informationIdentifier);
    }
    public static function sendRemoteCommand($command, $expectReturnText = false) {
        return Playground \ RemoteCommand::sendRemoteCommand($command, $expectReturnText);
    }
};
