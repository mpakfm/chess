<?php

namespace Chess\Model;

use Chess\Board;
use Chess\ColorEnum;
use Chess\LetterTypesEnum;
use Exception;
use RuntimeException;

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

    public function checkMove(array $start, array $end): bool
    {
        try {
            $startLetter = $start['letter'] ?? null;
            $startLine = $start['line'] ?? null;

            $endLetter = $end['letter'] ?? null;
            $endLine = $end['line'] ?? null;

            $startLetter = LetterTypesEnum::validLetter($startLetter);
            $startLetterId = LetterTypesEnum::getIdBySystemName($startLetter);
            $startLine = Board::validLine($startLine);

            $endLetter = LetterTypesEnum::validLetter($endLetter);
            $endLetterId = LetterTypesEnum::getIdBySystemName($endLetter);
            $endLine = Board::validLine($endLine);

            $diffLetter = $endLetterId - $startLetterId;
            $diffLine = $endLine - $startLine;

            if (!$diffLetter && !$diffLine){
                throw new RuntimeException('Move is equal!');
            }

            switch ($this->color){
                case ColorEnum::WHITE: $isMoveProgress = ($diffLine > 0); break;
                case ColorEnum::BLACK: $isMoveProgress = ($diffLine < 0); break;
                default: $isMoveProgress = false;
            }

            if (!$isMoveProgress){
                throw new RuntimeException('Move should be progress!');
            }

            $isFirstMove = $this->isFirstMove($startLetterId, $startLine);

            $diffLetterAbs = abs($diffLetter);
            $diffLineAbs = abs($diffLine);

            if ($diffLetterAbs === 0){
                switch ($diffLineAbs){
                    case 1: {
                        $this->parent->checkCellIsFree($endLetter, $endLine);
                    }
                    break;

                    case 2:{
                        if (!$isFirstMove){
                            throw new RuntimeException('A pawn can go straight 2 squares forward only if it hasn’t yet!');
                        }

                        if ($startLine < $endLine){
                            $checkLineBegin = $startLine + 1;
                            $checkLineEnd = $endLine;
                        }else{
                            $checkLineBegin = $endLine;
                            $checkLineEnd = $startLine - 1;
                        }

                        for ($i = $checkLineBegin; $i <= $checkLineEnd; $i++) {
                            $this->parent->checkCellIsFree($endLetter, $i);
                        }
                    }
                    break;

                    default:{
                        throw new RuntimeException('A pawn can go straight only 1 or 2 squares forward!');
                    }
                }
            }else{
                if ($diffLetterAbs !== 1 && $diffLineAbs !== 1){
                    throw new RuntimeException('A pawn can go right diagonally only 1 square!');
                }

                $exists = $this->parent->getCell($endLetter, $endLine);

                if (!$exists){
                    throw new RuntimeException('A pawn can go right diagonally only 1 square and only if there is an piece!');
                }

                if ($exists->color === $this->color){
                    throw new RuntimeException('A pawn can go right diagonally only 1 square and only if there is an enemy piece!');
                }
            }
        } catch (RuntimeException $e){
            return false;
        } catch (Exception $e){
            return false;
        }

        return true;
    }
}
