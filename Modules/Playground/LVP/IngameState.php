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

class IngameState {
    public static function onlinePlayers() {
        $database = Database::instance();
        $query = $database->query('
            SELECT
                online.*,
                users.level,
                users.is_developer,
                users.is_vip
            FROM
                online
            LEFT JOIN
                users ON users.user_id = online.user_id
            ORDER BY
                online.nickname ASC');
        
        $onlinePlayers = array();
        while ($query !== false && $row = $query->fetch_assoc()) {
            $playerData = self::createPlayerDataFromRow($row);
            $onlinePlayers[$row['player_id']] = $playerData;
        }
        
        return $onlinePlayers;
    }

    public static function getOnlinePlayerById($id) {
        $database = Database::instance();
        $query = $database->query('
            SELECT
                online.*,
                users.level,
                users.is_developer,
                users.is_vip
            FROM 
                online
            LEFT JOIN
                users ON users.user_id = online.user_id
            WHERE
                player_id = ' . (int) $id);

        if ($query !== false && $row = $query->fetch_assoc()) {
            return self::createPlayerDataFromRow($row);
        }

        return null;
    }

    public static function findOnlinePlayersByPartialNickname($nickname) {
        $database = Database::instance();
        $statement = $database->prepare('
            SELECT
                online.player_id, online.nickname, online.user_id, online.score,
                online.position_x, online.position_y, online.position_z, online.health,
                online.armor, online.color, online.ip_address, online.join_date,
                users.level, users.is_developer, users.is_vip
            FROM 
                online
            LEFT JOIN
                users ON users.user_id = online.user_id
            WHERE
                nickname LIKE ?');
        $wildcardNickname = '%' . $nickname . '%';
        $statement->bind_param('s', $wildcardNickname);
        $statement->execute();

        $onlinePlayers = array();

        $row = array();
        $statement->bind_result($row['player_id'], $row['nickname'], $row['user_id'], $row['score'],
            $row['position_x'], $row['position_y'], $row['position_z'], $row['health'],
            $row['armor'], $row['color'], $row['ip_address'], $row['join_date'],
            $row['level'], $row['is_developer'], $row['is_vip']);

        while ($statement->fetch()) {
            $playerData = self::createPlayerDataFromRow($row);
            $onlinePlayers[$row['player_id']] = $playerData;
        }

        return $onlinePlayers;
    }

    private static function createPlayerDataFromRow($row) {
        $playerData = array(
            'nickname'      => $row['nickname'],
            'ip_address'    => long2ip($row['ip_address']),
            'joined'        => $row['join_date'],
            
            'score'         => $row['score'],
            'health'        => $row['health'],
            'armor'         => $row['armor'],
            'color'         => $row['color'],
            'position'      => array(
                'x'         => $row['position_x'],
                'y'         => $row['position_y'],
                'z'         => $row['position_z']
            ),
            
            'account'       => false
        );

        if ($row['user_id'] != 0) {
            $playerData['account'] = array(
                'user_id'       => $row['user_id'],
                'level'         => $row['level'],
                'is_developer'  => $row['is_developer'],
                'is_vip'        => $row['is_vip']
            );
        }

        return $playerData;
    }

};
