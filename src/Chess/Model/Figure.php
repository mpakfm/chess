<?php

namespace Chess\Model;

use Chess\ColorEnum;
use Chess\LetterTypesEnum;

/**
 * Created by PhpStorm.
 * User: mpak
 * Date: 08.02.20
 * Time: 18:25
 */

abstract class Figure {

    /**
     * @var int
     */
    public $color;

    /**
     * @var string
     */
    public $symbol;

    /**
     * @var array
     */
    protected $symbolVariant;

    public static function create(string $letter, int $line): ?Figure {
        $letterId = LetterTypesEnum::getIdBySystemName($letter);
        if ($line < 1 || $line > 8) {
            throw new \Exception('Wrong cell');
        }
        if ($letterId < 1 || $letterId > 8) {
            throw new \Exception('Wrong cell');
        }
        if ($line <= 2) {
            $color = ColorEnum::WHITE;
        } elseif ($line >= 7) {
            $color = ColorEnum::BLACK;
        } else {
            return null;
        }
        if ($line == 2 || $line == 7) {
            return new Pawn($color);
        }
        switch ($letterId) {
            case "1": case "8": return new Rook($color);
            case "2": case "7": return new Knight($color);
            case "3": case "6": return new Bishop($color);
            case "4": return new Queen($color);
            case "5": return new King($color);
        }
    }

    public function __construct(int $color) {
        if (!in_array($color, ColorEnum::getEnumIds())) {
            throw new \Exception('Unknown color');
        }
        $this->color  = $color;
        $this->symbol = $this->symbolVariant[$color];
    }

    abstract public function checkMove(array $start, array $end): bool;
}
