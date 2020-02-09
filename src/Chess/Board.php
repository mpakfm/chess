<?php

namespace Chess;

use Chess\Model\Figure;

/**
 * Created by PhpStorm.
 * User: mpak
 * Date: 08.02.20
 * Time: 18:48
 */

class Board {

    /**
     * @var array
     */
    private $matrix;

    public function __construct() {
        $this->initBoard();
    }

    public static function getCellColor(string $letter, int $line) {
        $letterId = LetterTypesEnum::getIdBySystemName($letter);
        if ($line < 1 || $line > 8) {
            throw new \Exception('Wrong cell');
        }
        if ($letterId < 1 || $letterId > 8) {
            throw new \Exception('Wrong cell');
        }
        return (int) (!(($line + $letterId) % 2));
    }

    private function initBoard(): void {
        $this->matrix = [];
        for ($line = 8; $line >= 1; $line--) {
            for ($letter = 1; $letter <= 8; $letter++) {
                $letterName = LetterTypesEnum::getSystemName($letter);
                $figure     = Figure::create($letterName, $line);

                $this->matrix[$line][$letterName] = $figure;
            }
        }
    }

    public function getCell($letter, $line): ?Figure {
        return $this->matrix[$line][$letter];
    }

    public function setMove(array $start, array $end): void {
        $figure = $this->getCell($start['letter'], $start['line']);
        if (!$figure) {
            throw new \Exception(Game::WRONG_FIGURE_MESSAGE);
        }
        $this->matrix[$end['line']][$end['letter']]     = $figure;
        $this->matrix[$start['line']][$start['letter']] = null;
    }

    public function drow(): void {
        echo "  " . implode(' ', LetterTypesEnum::getSystemNames()) . "\n";
        echo " ┏━━━━━━━━━━━━━━━┓\n";
        foreach ($this->matrix as $number => $line) {
            echo $number . '┃';
            $str = [];
            foreach ($line as $letter => $figure) {
                if (!$figure) {
                    $str[] = ' ';
                } else {
                    $str[] = $figure->symbol;
                }
            }
            echo implode(' ', $str);
            echo "┃\n";
        }
        echo " ┗━━━━━━━━━━━━━━━┛\n";
        echo "  " . implode(' ', LetterTypesEnum::getSystemNames()) . "\n";
    }
}
