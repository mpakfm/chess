<?php

namespace Chess;

use Chess\Board;
use Mpakfm\Printu;

/**
 * Created by PhpStorm.
 * User: mpak
 * Date: 08.02.20
 * Time: 18:49
 */

class Game {

    /**
     * @var Board
     */
    public $board;

    public function __construct() {
        $this->board = new Board();
    }

    public function run() {
        echo "Input your move: (e2-e4)\n";
        $stdin    = fopen('php://stdin', 'r');
        $response = trim(fgets($stdin));
        if ($response == '') {
            $response = 'e2-e4';
        }
        Printu::log('"' . $response . '"', 'response', 'ajax');
    }
}
