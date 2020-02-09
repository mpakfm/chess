<?php

namespace test\Game;

use Chess\Board;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: mpak
 * Date: 08.02.20
 * Time: 23:47
 */

class BoardTest extends TestCase {
    public function testGetCellColor() {
        assertEquals(1, Board::getCellColor('a', 1));
        assertEquals(0, Board::getCellColor('b', 1));
        assertEquals(1, Board::getCellColor('b', 2));
        assertEquals(0, Board::getCellColor('e', 2));
        assertEquals(0, Board::getCellColor('e', 4));
        assertEquals(1, Board::getCellColor('h', 8));
    }
}
