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

require "classes/ErrorInScriptException.php";
require "classes/Test.php";
require "classes/Tester.php";

$tester = new Tester();
$script = "tmp/sheet"; // There is no extension in GNU/Linux OSes, so it's correct
$f = "test/files";

// Callback for successful tests (required for automation)
$successCallback = function (int $number, string $name) {
    echo GREEN."[{$number}] {$name}: The test was successful.".WHITE.PHP_EOL;
};
$failCallback = function (ErrorInScriptException $e) {
    $type = $e->getType() === ErrorInScriptException::TYPE_BAD_OUTPUT ? "Output error" : "Exit code error";
    echo RED."[{$e->getNumber()}] {$e->getTest()}: {$type} - {$e->getMessage()}".WHITE.PHP_EOL;
};

// STANDARD BEHAVIOUR TESTS
// ========================
// Simple call
$tester->createTest()
    ->setName("Simple call without parameters (=> without changes)")
    ->setScript($script)
    ->setFileInput("{$f}/0-school-input.txt")
    ->setFileExpOutput("{$f}/1-simple-call.txt");

// TESTS ACCORDING TO SCHOOL SAMPLES
// =================================
// Add week column
$tester->createTest()
    ->setName("Add column to the left (1st school sample)")
    ->setScript($script)
    ->addParams("-d : icol 1")
    ->addParams("-d : rows 1 1 cset 1 Tyden")
    ->setFileInput("{$f}/0-school-input.txt")
    ->setFileExpOutput("{$f}/2-add-week-column.txt");
// Fill week column
$tester->createTest()
    ->setName("Fill week column (2nd school sample)")
    ->setScript($script)
    ->addParams("-d : rseq 1 2 - 1")
    ->setFileInput("{$f}/2-add-week-column.txt") // Follows the previous test
    ->setFileExpOutput("{$f}/3-fill-week-column.txt");
// Add points summary row
$tester->createTest()
    ->setName("Add points summary row (3rd school sample)")
    ->setScript($script)
    ->addParams("-d : arow")
    ->addParams("-d : rows - - cset 2 \"celkem bodu\"")
    ->setFileInput("{$f}/3-fill-week-column.txt") // Follows the previous test
    ->setFileExpOutput("{$f}/4-add-points-sum-row.txt");
// Count points summary
$tester->createTest()
    ->setName("Count points summary (4th school sample)")
    ->setScript($script)
    ->addParams("-d : rsum 3 2 14")
    ->setFileInput("{$f}/4-add-points-sum-row.txt")
    ->setFileExpOutput("{$f}/5-count-points-sum.txt");

// ELEMENTARY FUNCTIONS TESTS
$elmFunInput = "{$f}/0-elementary-functions-input.txt";
// Add row before another row (irow R)
$tester->createTest()
    ->setName("Add row before another row")
    ->setScript($script)
    ->addParams("-d ; irow 3")
    ->setFileInput($elmFunInput)
    ->setFileExpOutput("{$f}/6-add-row-before.txt");
// Append row to the end (arow)
$tester->createTest()
    ->setName("Append row to the end")
    ->setScript($script)
    ->addParams("-d ; arow")
    ->setFileInput($elmFunInput)
    ->setFileExpOutput("{$f}/7-append-row.txt");
// Delete single row (drow R)
$tester->createTest()
    ->setName("Delete single row")
    ->setScript($script)
    ->addParams("-d ; drow 2")
    ->setFileInput($elmFunInput)
    ->setFileExpOutput("{$f}/8-delete-single-row.txt");
// Delete single row II (drows R R)
$tester->createTest()
    ->setName("Delete single row II (with drows)")
    ->setScript($script)
    ->addParams("-d ; drows 2 2")
    ->setFileInput($elmFunInput)
    ->setFileExpOutput("{$f}/8-delete-single-row.txt");
// Delete multiple rows (drows M N)
$tester->createTest()
    ->setName("Delete multiple rows")
    ->setScript($script)
    ->addParams("-d ; drows 2 4")
    ->setFileInput($elmFunInput)
    ->setFileExpOutput("{$f}/10-delete-multiple-rows.txt");

$tester->runTests($successCallback, $failCallback);

// Summary report
$successRow = sprintf("Successful tests:\t%d / %d (%d %%)", $tester->getSuccessful(), $tester->getTestsSum(), $tester->getSuccessRate());
$failRow = sprintf("Failed tests:\t\t%d / %d (%d %%)", $tester->getFailed(), $tester->getTestsSum(), $tester->getFailRate());
$skipRow = sprintf("Skipped tests: \t\t%d / %d (%d %%)", $tester->getSkipped(), $tester->getTestsSum(), $tester->getSkipRate());

echo WHITE.str_repeat("=", 37).PHP_EOL;
echo GREEN.$successRow.WHITE.PHP_EOL;
echo RED.$failRow.WHITE.PHP_EOL;
echo WHITE.$skipRow.PHP_EOL;

// Motivational easter eggs
if (($tester->getSuccessRate() + $tester->getSkipRate()) < 50) {
    echo PHP_EOL.RED."\"Hej, tvůj soft... k hovnu.\" - Ivan Vanko".WHITE.PHP_EOL;
}

if ($tester->getSkipRate() > 50) {
    echo PHP_EOL.RED."Hmm, I'm looking at your skipped tests... It sounds like very bad joke.".WHITE.PHP_EOL;
}

// Exit code for Gitlab
exit($tester->getFinalExitCode());