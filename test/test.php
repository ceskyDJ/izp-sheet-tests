<?php

declare(strict_types=1);

// Script input params
// test.php -c          Activate extended color mode (with background color)
$args = getopt("c::");

// Colors for terminal outputs
if (key_exists("c", $args)) {
    define("GREEN", "\e[0;32;40m");
    define("RED", "\e[0;31;40m");
    define("WHITE", "\e[0m");
} else {
    define("GREEN", "\e[0;32m");
    define("RED", "\e[0;31m");
    define("WHITE", "\e[0m");
}

require "ErrorInScriptException.php";
require "Tester.php";

$tester = new Tester();
$script = "tmp/sheet"; // There is no extension in GNU/Linux OSes, so it's correct
$f = "test/files";

// Callback for successful tests (required for automation)
$successCallback = function ($number, $name) {
    echo GREEN."[{$number}] {$name}: was successful".WHITE.PHP_EOL;
};

// Individual tests
try {
    $tester->testFileInput(
        "Simple call without parameters (=> without changes)",
        $script,
        "",
        "{$f}/0-sample-input.txt",
        "{$f}/1-simple-call.txt",
        0, $successCallback
    );
} catch (ErrorInScriptException $e) {
    $type = $e->getType() === ErrorInScriptException::TYPE_BAD_OUTPUT ? "Stdout error" : "Exit code error";
    echo RED."[{$e->getNumber()}] {$e->getTest()}: {$type} - {$e->getMessage()}".WHITE.PHP_EOL;
}

// Summary report
$successRow = sprintf("Successful tests:\t%d / %d (%d %%)", $tester->getSuccessful(), $tester->getRan(), $tester->getSuccessRate());
$failRow = sprintf("Failed tests:\t\t%d / %d (%d %%)", $tester->getFailed(), $tester->getRan(), $tester->getFailRate());

echo WHITE.str_repeat("=", 37).PHP_EOL;
echo GREEN.$successRow.WHITE.PHP_EOL;
echo RED.$failRow.WHITE.PHP_EOL;