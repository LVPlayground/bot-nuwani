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

use \LVP;

/**
 * @see http://trac.sa-mp.nl/lvp/browser/LVP/gamemode/trunk/Features/Account/BanManager.pwn
 */
class BanManager {
    const BanEntry = 'ban';
    const KickEntry = 'kick';
    const NoteEntry = 'note';
    const UnbanEntry = 'unban';

    // If an administrator does not specify how long a ban should last for, what is the default
    // duration we should apply to the ban? Specify this in number of seconds.
    const DefaultBanDuration = 259200 /** 3 days **/;

    // Usage i.e. BanManager::GetPlayerLog('Russell', 0, 10); to get the first ten entries.
    //
    // Returns two dimensional array, with each array in this format:
    // array(
    //     'entry_id' => 0,
    //     'date' => 1234567890,
    //     'type' => ['ban', 'kick', 'log'],
    //     'admin' => 'Russell',
    //     'player' => 'Cheater',
    //     'message' => 'Moneycheat'
    // )
    // The key total_results contains the number of total results for the search query.
    public static function GetPlayerLog($nickname, $offsetStart = 0, $offsetLimit = 5) {
        $profile = LVP::findProfileByNickname($nickname);
        $database = Database::instance();

        $queryTemplate = 'SELECT %s FROM logs WHERE %s ORDER BY log_date DESC %s';
        $columns = 'log_id, log_date, log_type,
            user_nickname, user_id, subject_nickname, subject_user_id,
            ban_ip_range_start, ban_ip_range_end, ban_expiration_date, description';
        $filter = 'subject_nickname = "' . $database->real_escape_string($nickname) . '"';
        if ($profile->exists()) {
            $filter .= ' OR subject_user_id = ' . (int) $profile['user_id'];
        }

        $query = $database->query(sprintf($queryTemplate, 'COUNT(*)', $filter, ''));
        if ($query !== false && $row = $query->fetch_row()) {
            $totalRecords = $row[0];
        }

        if ($totalRecords == 0) {
            return false;
        }

        $query = $database->query(sprintf($queryTemplate, $columns, $filter, sprintf('LIMIT %d,%d', $offsetStart, $offsetLimit)));

        $result = array();
        while ($query !== false && $row = $query->fetch_assoc()) {
            $result[] = array(
                'entry_id'  => $row['log_id'],
                'date'      => self::formatDate($row['log_date']),
                'duration'  => strtotime($row['ban_expiration_date']) - strtotime($row['log_date']),
                'type'      => $row['log_type'],
                'admin'     => $row['user_nickname'],
                'player'    => $row['subject_nickname'],
                'message'   => trim($row['description']),
                'ip'        => self::formatIpAddressRange($row['ban_ip_range_start'], $row['ban_ip_range_end'])
            );
        }

        $result['total_results'] = $totalRecords;

        return $result;
    }
    
    // Usage i.e. BanManager::AddEntryForPlayer('OmgCheater', 'Russell', BanManager::BanEntry, 'Bad polski!');
    public static function AddEntryForPlayer($username, $administrator, $type, $message) {
        self::createEntry($type, $username, $administrator, $message);
    }

    // Usage i.e. BanManager::FindBannedIp('192.168.1.2');
    // 
    // Returns false in case of no result, otherwise an array with information
    // about the ban in the same format as GetRecentBans().
    public static function FindBannedIp($address) {
        $database = Database::instance();
        $statement = $database->prepare(
            'SELECT
                log_date, ban_ip_range_start, ban_ip_range_end, ban_expiration_date,
                user_nickname, user_id, subject_nickname, subject_user_id, description
            FROM
                logs
            WHERE
                log_type = "ban" AND
                ban_expiration_date > NOW() AND
                (ban_ip_range_start <= INET_ATON(?) AND ban_ip_range_end >= INET_ATON(?))
            ORDER BY
                log_date DESC
            LIMIT 1');
        $statement->bind_param('ss', $address, $address);
        $result = array();
        $statement->bind_result($result['log_date'], $result['ban_ip_range_start'], $result['ban_ip_range_end'],
            $result['ban_expiration_date'], $result['user_nickname'], $result['user_id'],
            $result['subject_nickname'], $result['subject_user_id'], $result['description']);
        $statement->execute();

        if ($statement->fetch()) {
            return array(
                'ip'                    => self::formatIpAddressRange($result['ban_ip_range_start'], $result['ban_ip_range_end']),
                'date'                  => $result['log_date'],
                'expiration_date'       => $result['ban_expiration_date'],
                'player'                => $result['subject_nickname'],
                'player_user_id'        => $result['subject_user_id'],
                'administrator'         => $result['user_nickname'],
                'administrator_user_id' => $result['user_id'],
                'message'               => $result['description']
            );
        }

        return false;
    }
    
    // Usage i.e. BanManager::GetRecentBans(5); to get the last five bans.
    //
    // Returns a two dimensional array, with each array in this format:
    // array(
    //     'ip' => '127.0.0.2',
    //     'player' => 'Chitter',
    //     'message' => 'Weaponcheats'
    // )
    public static function GetRecentBans($limit = 5) {
        $database = Database::instance();
        $query = $database->query(
            'SELECT
                log_date, ban_ip_range_start, ban_ip_range_end, ban_expiration_date,
                user_nickname, user_id, subject_nickname, subject_user_id, description
            FROM
                logs
            WHERE
                log_type = "ban" AND
                ban_expiration_date > NOW()
            ORDER BY
                log_date DESC
            LIMIT 5');

        $result = array();
        while ($query !== false && $row = $query->fetch_assoc()) {
            $result[] = array(
                'ip'                    => self::formatIpAddressRange($row['ban_ip_range_start'], $row['ban_ip_range_end']),
                'date'                  => $row['log_date'],
                'expiration_date'       => $row['ban_expiration_date'],
                'player'                => $row['subject_nickname'],
                'player_user_id'        => $row['subject_user_id'],
                'administrator'         => $row['user_nickname'],
                'administrator_user_id' => $row['user_id'],
                'message'               => $row['description']
            );
        }

        return $result;
    }
    
    // Usage i.e. BanIp('127.0.0.2', 'OmgCheater', 'Russell', 'Y U CHIT?');
    public static function BanIp($address, $username, $administrator, $duration, $reason) {
        self::createEntry(self::BanEntry, $username, $administrator, $reason, $address, $address, time() + ($duration / 86400));
    }
    
    // Usage i.e. UnbanIp('127.0.0.7', 'Russell', 'He has been nice.');
    public static function UnbanIp($address, $administrator, $note) {
        $existingBan = self::FindBannedIp($address);
        if (!$existingBan) {
            return null;
        }

        // Unban a player by setting ban_expiration_date to NOW()
        $database = Database::instance();
        $statement = $database->prepare(
            'UPDATE
                logs
            SET
                ban_expiration_date = NOW()
            WHERE
                log_type = "ban" AND
                ban_expiration_date > NOW() AND
                (ban_ip_range_start <= INET_ATON(?) AND ban_ip_range_end >= INET_ATON(?))');
        $statement->bind_param('ss', $address, $address);
        $statement->execute();

        self::createEntry(self::UnbanEntry, $existingBan['player'], $administrator, $note);

        return $existingBan;
    }

    //// Private helper methods ////

    private static function formatDate($date) {
        return date('d-m-Y H:i:s', strtotime($date));
    }

    private static function formatIpAddressRange($rangeStart, $rangeEnd) {
        if ($rangeStart == 0) {
            return '';
        }

        $ipStart = long2ip($rangeStart);
        if ($rangeEnd == 0) {
            return $ipStart;
        }

        $ipEnd = long2ip($rangeEnd);
        if ($ipStart == $ipEnd) {
            return $ipStart;
        }

        // TODO Format this as 192.168.*.*
        return $ipStart . '-' . $ipEnd;
    }

    private static function createEntry($type, $username, $administrator, $message, $rangeStart = 0, $rangeEnd = 0, $expirationDate = 0) {
        $userProfile = LVP::findProfileByNickname($username);
        $adminProfile = LVP::findProfileByNickname($administrator);

        $database = Database::instance();
        $statement = $database->prepare(
            'INSERT INTO logs (
                log_date, log_type, ban_ip_range_start, ban_ip_range_end, ban_expiration_date,
                user_nickname, user_id, subject_nickname, subject_user_id, description
            )
            VALUES (
                NOW(), ?, INET_ATON(?), INET_ATON(?), FROM_UNIXTIME(?), ?, ?, ?, ?, ?
            )');
        $userId = $userProfile->exists() ? $userProfile['user_id'] : 0;
        $adminId = $adminProfile->exists() ? $adminProfile['user_id'] : 0;
        $statement->bind_param('sssssisis', $type, $rangeStart, $rangeEnd, $expirationDate,
            $administrator, $adminId, $username, $userId, $message);
        $statement->execute();
        $statement->close();
    }
};
