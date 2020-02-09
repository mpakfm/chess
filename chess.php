<?php

use Chess\Game;
use Mpakfm\Printu;
use Tools\ConsoleColors;

include_once __DIR__ . "/vendor/autoload.php";

Printu::setPath(__DIR__ . '/log');

echo "\n" . ConsoleColors::green('Init Chess') . "\n\n";

$game = new Game();
$game->board->drow();
$game->run();

//echo '♙♘♗♖♕♔' . "\n";
//echo '♟♞♝♜♛♚' . "\n";
//Printu::log(\Chess\TypeCaster::num2color(1), '1', 'ajax');
//Printu::log(\Chess\TypeCaster::color2num('black'), 'black', 'ajax');
//Printu::log(\Chess\TypeCaster::num2letter('1'), 'num2letter 1', 'ajax');
//Printu::log(\Chess\TypeCaster::letter2num('e'), 'letter2num e', 'ajax');
//Printu::log(\Chess\TypeCaster::letter('e'), 'letter e', 'ajax');
//Printu::log(\Chess\TypeCaster::letter('E'), 'letter E', 'ajax');
//Printu::log(\Chess\TypeCaster::letter('t'), 'letter t', 'ajax');
//Printu::log(\Chess\TypeCaster::letter('3'), 'letter 3', 'ajax');


