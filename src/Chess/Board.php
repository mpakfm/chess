<?php

namespace Chess;

use Chess\Model\Figure;
use RuntimeException;

/**
 * Created by PhpStorm.
 * User: mpak
 * Date: 08.02.20
 * Time: 18:48
 */

class Board {
    public const LINE_BEGIN = 1;
    public const LINE_END = 8;

    /** @var Game */
    public $parent;

    /**
     * @var Figure[]
     */
    private $matrix;

    public function __construct(Game $parent) {
        $this->parent = $parent;

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

        $letters = LetterTypesEnum::getSystemNames();

        for ($line = self::LINE_END; $line >= self::LINE_BEGIN; $line--) {
            foreach ($letters as $letter => $letterName){
                $figure     = Figure::create($this, $letterName, $line);

                $this->matrix[$line][$letterName] = $figure;
            }
        }
    }

    public function getCell($letter, $line): ?Figure {
        return $this->matrix[$line][$letter];
    }

    /**
     * @param $letter
     * @param $line
     * @return bool
     * @throws RuntimeException
     */
    public function checkCellIsFree($letter, $line): bool
    {
        $exists = $this->getCell($letter, $line);

        if ($exists){
            throw new RuntimeException('Cell is already taken');
        }

        return true;
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

    /**
     * Проверяет линию на корректность значения и вхождение в диапазон, возвращает ошибку или значение
     *
     * @param mixed $value
     * @return int
     * @throws RuntimeException
     */
    public static function validLine($value): int
    {
        $line = (int)$value;

        if (!$line){
            throw new RuntimeException('Invalid letter');
        }

        $result = filter_var(
            $line,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => self::LINE_BEGIN,
                    'max_range' => self::LINE_END
                ]
            ]
        );

        if (!$result) {
            throw new RuntimeException('Invalid letter');
        }

        return $line;
    }
}
