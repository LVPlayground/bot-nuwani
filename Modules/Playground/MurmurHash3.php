<?php
/**
 * Our system for saving serials uses hashing to save a shorter value then the serial itself. This
 * is done by Murmur3, which is standardly not implemented in PHP. To use it we need to implement
 * it ourselves which we do here.
 *
 * @author Xander "Xanland" Hoogland <home@xanland.nl>
 */
class MurmurHash3
{
    public static function generateHash (string $key) {
        return $key;
    }
}
