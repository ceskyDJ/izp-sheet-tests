<?php

declare(strict_types=1);

define("GREEN", "\e[0;32;40m");
define("RED", "\e[0;31;40m");
define("WHITE", "\e[0m");

require 'ErrorInScriptException.php';
require 'Tester.php';

$tester = new Tester();
$script = "sheet";

try {
    $tester->test($name = "Test 1", $script, "nic", ["123", "321"], ["123"]);

    echo GREEN."({$tester->getRan()}) {$name}: was successful".WHITE.PHP_EOL;
} catch (ErrorInScriptException $e) {
    $type = $e->getType() === ErrorInScriptException::TYPE_BAD_OUTPUT ? "stdout" : "error code";
    echo RED."({$e->getNumber()}) {$e->getTest()}: {$type} - {$e->getMessage()}".WHITE.PHP_EOL;
}