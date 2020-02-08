<?php

use Mpakfm\Printu;

include_once __DIR__ . "/vendor/autoload.php";

Printu::setPath(__DIR__ . '/log');

Printu::log('init', 'chess', 'ajax');
Printu::log('init', 'chess', 'file');

$tmp = [
    'school' =>'',
    'try' => 34
];
