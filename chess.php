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
