<?php

namespace Chess;

use Tools\EnumAbstract;

/**
 * Created by PhpStorm.
 * User: mpak
 * Date: 08.02.20
 * Time: 19:24
 */

class ColorEnum extends EnumAbstract {
    public const WHITE = 0;
    public const BLACK = 1;

    /**
     * @param mixed $value
     */
    public static function validColorId($value) {
        $value = (int) $value;
        if (!self::isValidId($value)) {
            throw new \Exception('Invalid color id');
        }
        return $value;
    }
}
