<?php

namespace test\Game;

use Chess\ColorEnum;
use Chess\Model\Bishop;
use Chess\Model\Figure;
use Chess\Model\King;
use Chess\Model\Knight;
use Chess\Model\Pawn;
use Chess\Model\Queen;
use Chess\Model\Rook;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: mpak
 * Date: 08.02.20
 * Time: 23:00
 */

class FigureTest extends TestCase {
    public function testPawn() {
        $figure = Figure::create('a', 2);
        assertEquals('♙', $figure->symbol);
        $figure = Figure::create('b', 2);
        assertEquals('♙', $figure->symbol);
        $figure = Figure::create('c', 2);
        assertEquals('♙', $figure->symbol);
        $figure = Figure::create('h', 2);
        assertEquals('♙', $figure->symbol);
        $figure = Figure::create('a', 7);
        assertEquals('♟', $figure->symbol);
        $figure = Figure::create('f', 7);
        assertEquals('♟', $figure->symbol);
        $figure = Figure::create('g', 7);
        assertEquals('♟', $figure->symbol);
        $figure = Figure::create('h', 7);
        assertEquals('♟', $figure->symbol);
    }

    public function testRook() {
        $figure = Figure::create('a', 1);
        assertEquals('♖', $figure->symbol);
        $figure = Figure::create('h', 1);
        assertEquals('♖', $figure->symbol);
        $figure = Figure::create('a', 8);
        assertEquals('♜', $figure->symbol);
        $figure = Figure::create('h', 8);
        assertEquals('♜', $figure->symbol);
    }

    public function testKnight() {
        $figure = Figure::create('b', 1);
        assertEquals('♘', $figure->symbol);
        $figure = Figure::create('g', 1);
        assertEquals('♘', $figure->symbol);
        $figure = Figure::create('b', 8);
        assertEquals('♞', $figure->symbol);
        $figure = Figure::create('g', 8);
        assertEquals('♞', $figure->symbol);
    }

    public function testBishop() {
        $figure = Figure::create('c', 1);
        assertEquals('♗', $figure->symbol);
        $figure = Figure::create('f', 1);
        assertEquals('♗', $figure->symbol);
        $figure = Figure::create('c', 8);
        assertEquals('♝', $figure->symbol);
        $figure = Figure::create('f', 8);
        assertEquals('♝', $figure->symbol);
    }

    public function testQueen() {
        $figure = Figure::create('d', 1);
        assertEquals('♕', $figure->symbol);
        $figure = Figure::create('d', 8);
        assertEquals('♛', $figure->symbol);
    }

    public function testKing() {
        $figure = Figure::create('e', 1);
        assertEquals('♔', $figure->symbol);
        $figure = Figure::create('e', 8);
        assertEquals('♚', $figure->symbol);
    }
}