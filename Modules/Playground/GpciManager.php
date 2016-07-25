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

/**
 * This class handles everything which has to do with serials. Stuff from if it is valid, getting
 * results from the database and even converting it to murmur3 if someone is still searching for
 * matches related to our old serials.
 *
 * @author Xander "Xanland" Hoogland <home@xanland.nl>
 */
class GpciManager
{
    public static function IsValidHashedGpci (string $gpci) {
        var_dump(1);
        if (is_numeric($gpci) && strlen($gpci) > 8)
            return true;

        return false;
    }

    public static function IsValidGpci (string $gpci) {
        if (preg_match('/^[A-Za-z0-9]{39,}$/', $gpci))
            return true;

        return false;
    }

    public static function GetNicknamesByGpci (int $gpci) {
        echo 1;
        $database = Database::instance();
        if ($statement = $database->prepare('
                SELECT
                  sessions.nickname, count(sessions.session_id)
                FROM
                  sessions
                WHERE
                  sessions.gpci_hash = ?
                GROUP BY
                  sessions.nickname'))
        {
            $statement->bind_param('i', $gpci);
            $statement->execute();

            $result = $row = array();
            $bindResult = $statement->bind_result($nickname, $amount);
            var_dump($row);
            while ($bindResult !== false && $statement->fetch()) {
                $result[] = array (
                    'nickname'  => $nickname,
                    'amount'    => $amount
                );
            }

            return $result;
        }

        return false;
    }
}
