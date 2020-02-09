<?php

namespace Chess;

use Tools\EnumAbstract;

/**
 * Created by PhpStorm.
 * User: mpak
 * Date: 08.02.20
 * Time: 18:57
 */

class LetterTypesEnum extends EnumAbstract {
    public const A = 1;
    public const B = 2;
    public const C = 3;
    public const D = 4;
    public const E = 5;
    public const F = 6;
    public const G = 7;
    public const H = 8;

    /**
     * @param mixed $value
     */
    public static function validLetter($value) {
        if (!in_array($value, self::getSystemNames())) {
            throw new \Exception('Invalid letter');
        }
        return $value;
    }
}
