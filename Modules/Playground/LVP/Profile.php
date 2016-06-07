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

class Profile implements \ ArrayAccess, \ IteratorAggregate {
    private $m_userData;
    private static $m_passwordKey;
    
    public static function setPasswordHashKey($key) {
        self::$m_passwordKey = $key;
    }
    
    public static function findByUserId($userId) {
        $query = Database::instance()->query('
            SELECT
                users.*,
                users_mutable.*
            FROM
                users
            LEFT JOIN
                users_mutable ON users_mutable.user_id = users.user_id
            WHERE
                users.user_id = ' . (int)($userId));

        if ($query === false || $query->num_rows == 0)
            return new self(array());
        
        return new self($query->fetch_assoc());
    }
    
    public static function findByNickname($nickname) {
        $query = Database::instance()->query('
            SELECT
                users.*,
                users_mutable.*
            FROM
                users_nickname
            LEFT JOIN
                users ON users.user_id = users_nickname.user_id
            LEFT JOIN
                users_mutable ON users_mutable.user_id = users.user_id
            WHERE
                users_nickname.nickname = "' . Database::instance()->real_escape_string($nickname) . '"');

        if ($query === false || $query->num_rows == 0)
            return new self(array());
        
        return new self($query->fetch_assoc());
    }
    
    protected function __construct($userData) {
        $this->m_userData = $userData;
    }
    
    public function exists() {
        return is_array($this->m_userData) && isset($this->m_userData['username']);
    }

    public function isOnline() {
        if (!$this->exists())
            return false;

        $query = Database::instance()->query('SELECT * FROM online WHERE user_id = ' . (int) $this->m_userData['user_id']);

        if ($query === false || $query->num_rows == 0)
            return false;

        return true;
    }
    
    public function setUsername($nickname) {
        if ($this->exists() == false)
            return false;
        
        if (self::findByNickname($nickname)->exists())
            return false;
        
        $database = Database::instance();
        $database->query('UPDATE users_nickname SET nickname="' . $database->real_escape_string($nickname) .
            '" WHERE user_id=' . $this->m_userData['user_id'] . ' AND nickname="' . $database->real_escape_string($this->m_userData['username']) . '"');
        $database->query('UPDATE users SET username="' . $database->real_escape_string($nickname) . '" WHERE user_id=' . $this->m_userData['user_id']);
        $database->query('INSERT INTO nickname_changes (user_id, nickname, `date`) VALUES (' . $this->m_userData['user_id'] . ', "' .
            $database->real_escape_string($this->m_userData['username']) . '", NOW())');
        
        $this->m_userData['username'] = $nickname;
        
        return !strlen($database->error);
    }
    
    public function previousUsernames() {
        if ($this->exists() == false)
            return false;
        
        $database = Database::instance();
        $result = $database->query('SELECT nickname, `date` FROM nickname_changes WHERE user_id=' . $this->m_userData['user_id']);
        $nicknames = array();
        
        if ($result == false)
            return $nicknames;
        
        while ($row = $result->fetch_assoc())
            $nicknames[] = array(
                'nickname' => $row['nickname'],
                'date'     => $row['date']);
        
        return $nicknames;
    }

    public function lastNicknameChange() {
        if ($this->exists() == false)
            return false;

        $database = Database::instance();
        $result = $database->query('SELECT MAX(`date`) FROM nickname_changes WHERE user_id=' . $this->m_userData['user_id']);

        if ($result == false || $result->num_rows == 0)
            return false;

        list($date) = $result->fetch_row();
        return strtotime($date);
    }

    public function listNicknames() {
        if ($this->exists() == false)
            return false;
        
        $database = Database::instance();
        $result = $database->query('SELECT nickname FROM users_nickname WHERE user_id=' . $this->m_userData['user_id']);
        $nicknames = array();
        
        if ($result == false)
            return array($this->m_userData['username']);
        
        while ($row = $result->fetch_assoc())
            $nicknames[] = $row['nickname'];
        
        return $nicknames;
    }
    
    public function addNickname($nickname) {
        if ($this->exists() == false)
            return false;
        
        if (self::findByNickname($nickname)->exists())
            return false;
        
        $database = Database::instance();
        $database->query('INSERT INTO users_nickname (user_id, nickname) VALUES (' . $this->m_userData['user_id'] . ', "' .
            $database->real_escape_string($nickname) . '")');
        return !strlen($database->error);
    }
    
    public function removeNickname($nickname) {
        if ($this->exists() == false)
            return false;
        
        $existingPlayer = self::findByNickname($nickname);
        if ($existingPlayer->exists() == false || $existingPlayer['user_id'] != $this->m_userData['user_id'])
            return false;
        
        $database = Database::instance();
        $database->query('DELETE FROM users_nickname WHERE user_id=' . $this->m_userData['user_id'] . ' AND nickname="' .
            $database->real_escape_string($nickname) . '" LIMIT 1');
        return true;
    }
    
    public function setPassword($password) {
        if ($this->exists() == false)
            return false;

        if (self::$m_passwordKey == null || !strlen(self::$m_passwordKey))
            return false;

        $this->m_userData['password_salt'] = mt_rand(100000000, 999999999);
        $this->m_userData['password'] = sha1($this->m_userData['password_salt'] . $password . self::$m_passwordKey);
        
        $database = Database::instance();
        $database->query('UPDATE users SET password="' . $database->real_escape_string($this->m_userData['password']) . '", password_salt=' .
            $this->m_userData['password_salt'] . ' WHERE user_id=' . $this->m_userData['user_id']);
        return true;
    }
    
    public function validatePassword($password) {
        if ($this->exists() == false)
            return false;

        if (self::$m_passwordKey == null || !strlen(self::$m_passwordKey))
            return false;
        
        $passwordKey = sha1($this->m_userData['password_salt'] . $password . self::$m_passwordKey);
        return $this->m_userData['password'] == $passwordKey;
    }
    
    public function offsetGet($offset) {
        if ($this->exists() == false || $this->isPrivateField($offset))
            return null;
        
        return isset($this->m_userData[$offset]) ? $this->m_userData[$offset] : null;
    }
    
    public function offsetSet($offset, $value) {
        if ($this->exists() == false || !isset($this->m_userData[$offset]) || $this->isReadOnlyField($offset))
            return;
        
        $this->m_userData[$offset] = $value;
    }
    
    public function offsetExists($offset) {
        if ($this->exists() == false || $this->isPrivateField($offset))
            return false;
        
        return isset($this->m_userData[$offset]);
    }
    
    public function offsetUnset($offset) {
        return;
    }
    
    public function getIterator() {
        $fields = array();
        if ($this->exists()) {
            foreach ($this->m_aUserData as $field => $value) {
                if ($this->isPrivateField($field))
                    continue;
            
                $fields[$field] = $value;
            }
        }
        
        return new \ ArrayIterator($fields);
    }
    
    public function save() {
        if ($this->exists() == false)
            return;
        
        $updateStatement = Database::instance()->prepare('
            UPDATE
                users
            SET
                validated = ?,
                level = ?,
                is_developer = ?,
                is_vip = ?,
                is_vip_mod = ?,
                color = ?
            WHERE
                user_id = ?');
        
        if ($updateStatement === false)
            return;
        
        $updateMutableQuery = 'UPDATE users_mutable SET ';
        $database = Database::instance();

        foreach ($this->m_userData as $field => $value) {
            if ($this->isReadOnlyField($field) || $this->isUsersTableField($field))
                continue;
            
            $updateMutableQuery .= $field . '="' . $database->real_escape_string($value) . '", ';
        }
        
        $updateMutableQuery = substr($updateMutableQuery, 0, -2) . ' WHERE user_id = ' . ((int) $this->m_userData['user_id']);
        
        $updateStatement->bind_param('isiiiii', $this->m_userData['validated'], $this->m_userData['level'],
            $this->m_userData['is_developer'], $this->m_userData['is_vip'], $this->m_userData['is_vip_mod'],
            $this->m_userData['color'], $this->m_userData['user_id']);

        if ($updateStatement->execute() == false)
            return;

        $updateStatement->close();
        $database->query($updateMutableQuery);
    }
    
    // Use setPassword() to update a player's password.
    private function isPrivateField($field) {
        return $field == 'password' ||
               $field == 'password_salt';
    }
    
    // Use setNickname() to update a player's username.
    private function isReadOnlyField($field) {
        return $this->isPrivateField($field) ||
               $field == 'user_id' ||
               $field == 'username' ||
               $field == 'updated';
    }
    
    private function isUsersTableField($field) {
        return $field == 'validated' ||
               $field == 'level' ||
               $field == 'is_developer' ||
               $field == 'is_vip' ||
               $field == 'is_vip_mod' ||
               $field == 'color';
    }
};
