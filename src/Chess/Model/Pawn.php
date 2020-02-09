<?php

namespace Chess\Model;

use Chess\ColorEnum;

/**
 * Created by PhpStorm.
 * User: mpak
 * Date: 08.02.20
 * Time: 19:12
 */

class Pawn extends Figure {
    protected $symbolVariant = [
        ColorEnum::WHITE => '♙',
        ColorEnum::BLACK => '♟',
    ];

    public function checkMove(array $start, array $end): bool {
        if ($start['letter'] == $end['letter'] && $start['line'] == $end['line']) {
            return false;
        }
        return true;
    }
}
