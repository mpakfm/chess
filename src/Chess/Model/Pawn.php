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
}
