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

class Database extends \ MySQLi {
    const ConnectionTimeoutSeconds = 60;

    private static $m_hostname = '';
    private static $m_username = '';
    private static $m_password = '';
    private static $m_database = '';

    private static $m_instance = null;
    private static $m_connectionTime = 0;

    public static function setDatabaseInformation($hostname, $username, $password, $database) {
        self::$m_hostname = $hostname;
        self::$m_username = $username;
        self::$m_password = $password;
        self::$m_database = $database;
    }

    public static function setPersistentInstance($instance) {
        self::$m_instance = $instance;
        self::$m_connectionTime = time() + 86400 * 365;
    }

    public static function instance() {
        if (self::$m_instance == null || (time() - self::$m_connectionTime) > self::ConnectionTimeoutSeconds) {
            self::$m_instance = new self(true);
            self::$m_connectionTime = time();
        }

        return self::$m_instance;
    }

    public function __construct($singleton) {
        if ($singleton === false)
            throw new \Exception("This class should be used as singleton, please use Database::getInstance() instead of new Database()!");

        parent::__construct(self::$m_hostname, self::$m_username, self::$m_password, self::$m_database);
    }
};
