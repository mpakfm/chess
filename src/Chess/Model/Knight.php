<?php

namespace Chess\Model;

use Chess\ColorEnum;

/**
 * Created by PhpStorm.
 * User: mpak
 * Date: 08.02.20
 * Time: 22:52
 */

class Knight extends Figure {
    protected $symbolVariant = [
        ColorEnum::WHITE => '♘',
        ColorEnum::BLACK => '♞',
    ];

    public function checkMove(array $start, array $end): bool {
        return true;
    }
}
