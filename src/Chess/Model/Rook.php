<?php

namespace Chess\Model;

use Chess\ColorEnum;

/**
 * Created by PhpStorm.
 * User: mpak
 * Date: 08.02.20
 * Time: 22:49
 */

class Rook extends Figure {
    protected $symbolVariant = [
        ColorEnum::WHITE => '♖',
        ColorEnum::BLACK => '♜',
    ];

    public function checkMove(array $start, array $end): bool {
        return true;
    }
}
