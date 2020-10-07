<?php

declare(strict_types=1);

/**
 * PHP console file for running test for C scripts
 *
 * @author Michal ŠMAHEL <admin@ceskydj.cz>
 * @author Vojtěch SVĚDIROH
 * @date October 2020
 */

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

$schoolInputFile = "{$f}/0-school-input.txt";

// Callback for successful tests (required for automation)
$successCallback = function ($number, $name) {
    echo GREEN."[{$number}] {$name}: The test was successful".WHITE.PHP_EOL;
};

// Individual tests
try {
    // Simple call
    $tester->testFileInput(
        "Simple call without parameters (=> without changes)",
        $script,
        "",
        $schoolInputFile,
        "{$f}/1-simple-call.txt",
        0,
        $successCallback
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