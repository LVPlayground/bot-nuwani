<?php
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
        if ((is_numeric($gpci) && strlen($gpci) > 10))
            return true;

        return false;
    }

    public static function IsValidGpci (string $gpci) {
        if (preg_match('/^[A-Za-z0-9]{39,}$/', $gpci))
            return true;

        return false;
    }

    public static function GetNicknamesByGpci (string $gpci) {
        $database = Database::instance();
        $statement = $database->prepare();
    }
}
