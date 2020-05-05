<?php

namespace Chess\Model;

use Chess\Board;
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
     * @var Board
     */
    public $parent;

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

    /**
     * @var string
     */
    protected $initialLetter;

    /**
     * @var int
     */
    protected $initialLetterId;

    /**
     * @var int
     */
    protected $initialLine;

    public static function create(Board $parent, string $letter, int $line): ?Figure {
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

        $figureClass = Pawn::class;

        if ($line === Board::LINE_BEGIN || $line === Board::LINE_END){
            switch ($letterId) {
                case "1": case "8": $figureClass = Rook::class; break;
                case "2": case "7": $figureClass = Knight::class; break;
                case "3": case "6": $figureClass = Bishop::class; break;
                case "4": $figureClass = Queen::class; break;
                case "5": $figureClass = King::class; break;
            }
        }

        return new $figureClass($parent, $color, $letter, $letterId, $line);
    }

    public function __construct(Board $parent, int $color, string $letter, int $letterId, int $line) {
        if (!in_array($color, ColorEnum::getEnumIds())) {
            throw new \Exception('Unknown color');
        }

        $this->parent = $parent;

        $this->color  = $color;
        $this->symbol = $this->symbolVariant[$color];

        $this->initialLetter = $letter;
        $this->initialLetterId = $letterId;
        $this->initialLine = $line;
    }

    abstract public function checkMove(array $start, array $end): bool;

    public function isFirstMove($letter, int $line): bool
    {
        $letterId = (int)$letter
            ? $letter
            : LetterTypesEnum::getIdBySystemName($letter);

        $result = ($letterId === $this->initialLetterId && $line === $this->initialLine);

        return $result;
    }
}
