<?php

namespace Chess;

use Chess\Board;
use Mpakfm\Printu;
use Tools\ConsoleColors;

/**
 * Created by PhpStorm.
 * User: mpak
 * Date: 08.02.20
 * Time: 18:49
 */

class Game {

    public const INPUT_DEFAULT_MESSAGE    = "Input your move: (e2-e4)\n";
    public const WRONG_MOVE_MESSAGE       = "Move format: e2-e4\n";
    public const WRONG_FIGURE_MESSAGE     = "Piece not found\n";
    public const WRONG_PIECE_MOVE_MESSAGE = "Piece can not to move to that space\n";
    public const WRONG_COLOR_MOVE_MESSAGE = "Piece of another color can to move\n";
    public const BYBY_MESSAGE             = "By-by\n";

    /**
     * @var Board
     */
    public $board;

    /**
     * @var bool
     */
    public $isFinish;

    /**
     * @var string
     */
    private $lastMove;

    /**
     * @var array
     */
    private $start;

    /**
     * @var array
     */
    private $end;

    /**
     * @var int
     */
    private $color;

    /**
     * @var array
     */
    private $listing;

    public function __construct() {
        $this->isFinish = false;
        $this->board    = new Board();
        $this->color    = ColorEnum::WHITE;
        $this->listing  = [];
    }

    public function run() {
        echo self::INPUT_DEFAULT_MESSAGE;
        while (!$this->isFinish) {
            try {
                $this->inputMove();
                $this->move();
                $this->drow();
            } catch (\Throwable $exception) {
                echo ConsoleColors::red($exception->getMessage() . "\n");
            }
        }

        echo ConsoleColors::green('Game over' . "\n");
    }

    public function inputMove() {
        $stdin    = fopen('php://stdin', 'r');
        $response = trim(fgets($stdin));
        $this->checkMove($response);
        Printu::log('"' . $response . '"', 'response', 'ajax');
    }

    public function drow() {
        $this->board->drow();
        echo self::INPUT_DEFAULT_MESSAGE;
    }

    public function move() {
        $this->board->setMove($this->start, $this->end);
        $this->listing[] = $this->lastMove;
        if ($this->color == ColorEnum::WHITE) {
            $this->color = ColorEnum::BLACK;
        } else {
            $this->color = ColorEnum::WHITE;
        }
    }

    private function checkMove(string $move) {
        if ($move == '') {
            throw new \Exception(self::WRONG_MOVE_MESSAGE);
        }
        if ($move == 'help') {
            $this->helpMessage();
            throw new \Exception(self::INPUT_DEFAULT_MESSAGE);
        }
        if ($move == 'exit') {
            $this->isFinish = true;
            throw new \Exception(self::BYBY_MESSAGE);
        }

        $parts = explode('-', strtolower($move));
        if (count($parts) != 2) {
            throw new \Exception(self::WRONG_MOVE_MESSAGE);
        }
        if (strlen($parts[0]) != 2 || strlen($parts[1]) != 2) {
            throw new \Exception(self::WRONG_MOVE_MESSAGE);
        }

        $this->start = [
            'letter' => TypeCaster::letter($parts[0][0]),
            'line'   => TypeCaster::int($parts[0][1]),
        ];
        $this->end = [
            'letter' => TypeCaster::letter($parts[1][0]),
            'line'   => TypeCaster::int($parts[1][1]),
        ];
        $figure = $this->board->getCell($this->start['letter'], $this->start['line']);
        if (!$figure) {
            throw new \Exception(self::WRONG_FIGURE_MESSAGE);
        }
        if ($figure->color != $this->color) {
            throw new \Exception(self::WRONG_COLOR_MOVE_MESSAGE);
        }
        if (!$figure->checkMove($this->start, $this->end)) {
            throw new \Exception(self::WRONG_PIECE_MOVE_MESSAGE);
        }
        $this->lastMove = $move;
    }

    private function helpMessage() {
        echo "exit - game over\n";
    }
}
