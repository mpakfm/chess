<?php

use PHPUnit\Framework\Assert;
use Tools\ConsoleColors;

// Определяем тестовое окружение
define('ROOT_SERVER', 'TEST');

$_SERVER["DOCUMENT_ROOT"] = __DIR__ . '/../';

include_once __DIR__ . "../vendor/autoload.php";

// Загружаем assert-функции из phpunit
require_once dirname((new ReflectionClass(Assert::class))->getFileName()) . '/Assert/Functions.php';

echo "\n" . ConsoleColors::green('Init tests') . "\n\n";
