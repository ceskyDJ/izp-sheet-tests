<?php

declare(strict_types=1);

/**
 * PHP console file for running test for C scripts
 *
 * @author Michal ŠMAHEL <admin@ceskydj.cz>
 * @author Vojtěch SVĚDIROH
 * @date October 2020
 */

mb_internal_encoding("UTF-8");

// Script input params
// test.php -c          Activate extended color mode (with background color)
$args = getopt("c::");

// Colors for terminal outputs
if (key_exists("c", $args)) {
    define("GREEN", "\e[0;32;40m");
    define("YELLOW", "\e[0;33;40m");
    define("RED", "\e[0;31;40m");
    define("WHITE", "\e[0m");
} else {
    define("GREEN", "\e[0;32m");
    define("YELLOW", "\e[0;33m");
    define("RED", "\e[0;31m");
    define("WHITE", "\e[0m");
}

require "classes/ErrorInScriptException.php";
require "classes/Test.php";
require "classes/Tester.php";

$tester = new Tester();
$script = "tmp/sheet"; // There is no extension in GNU/Linux OSes, so it's correct
$f = "files";

// Callback for successful tests (required for automation)
$newLevelCallback = function (int $level, string $name) {
    $message = "<< Started level {$level} ({$name}) >>";
    echo WHITE.str_repeat("-", mb_strlen($message)).PHP_EOL;
    echo YELLOW.$message.WHITE.PHP_EOL;
};
$successCallback = function (int $number, string $name) {
    echo GREEN."[{$number}] {$name}: The test was successful.".WHITE.PHP_EOL;
};
$failCallback = function (ErrorInScriptException $e) {
    $type = $e->getType() === ErrorInScriptException::TYPE_BAD_OUTPUT ? "Output error" : "Exit code error";
    $test = $e->getTest();
    echo RED."[{$test->getNumber()}] {$test->getName()}: {$type} - {$e->getMessage()}".WHITE.PHP_EOL;
    echo RED."\t{$test->getTestedCommands()}".WHITE;
};
$skipCallback = function (int $number, string $name) {
    echo WHITE."[{$number}] {$name}: The test was skipped.".PHP_EOL;
};

// STANDARD BEHAVIOUR
// ==================
$tester->startNewLevel(0, "Standard behaviour", $newLevelCallback);

// Simple call
$tester->createTest()
    ->setName("Simple call without parameters (=> without changes)")
    ->setScript($script)
    ->setFileInput("{$f}/0-school-input.txt")
    ->setFileExpOutput("{$f}/1-simple-call.txt");
// Many delimiters
$tester->createTest()
    ->setName("Many delimiters")
    ->setScript($script)
    ->addParams("-d :+-/")
    ->setFileInput("{$f}/0-many-delimiters-input.txt")
    ->setFileExpOutput("{$f}/16-many-delimiters.txt");

// ELEMENTARY FUNCTIONS
// ====================
$tester->startNewLevel(1, "Elementary functions", $newLevelCallback);
$elmFunInput = "{$f}/0-elementary-functions-input.txt";

// Add row before another row (irow R)
$tester->createTest()
    ->setName("Add row before another row")
    ->setScript($script)
    ->addParams("-d : irow 3")
    ->setFileInput($elmFunInput)
    ->setFileExpOutput("{$f}/6-add-row-before.txt");
// Append row to the end (arow)
$tester->createTest()
    ->setName("Append row to the end")
    ->setScript($script)
    ->addParams("-d : arow")
    ->setFileInput($elmFunInput)
    ->setFileExpOutput("{$f}/7-append-row.txt");
// Delete single row (drow R)
$tester->createTest()
    ->setName("Delete single row")
    ->setScript($script)
    ->addParams("-d : drow 2")
    ->setFileInput($elmFunInput)
    ->setFileExpOutput("{$f}/8-delete-single-row.txt");
// Delete single row II (drows R R)
$tester->createTest()
    ->setName("Delete single row II (with drows)")
    ->setScript($script)
    ->addParams("-d : drows 2 2")
    ->setFileInput($elmFunInput)
    ->setFileExpOutput("{$f}/8-delete-single-row.txt");
// Delete multiple rows (drows N M)
$tester->createTest()
    ->setName("Delete multiple rows")
    ->setScript($script)
    ->addParams("-d : drows 2 4")
    ->setFileInput($elmFunInput)
    ->setFileExpOutput("{$f}/10-delete-multiple-rows.txt");
// Add column before another column (icol C)
$tester->createTest()
    ->setName("Add column before another column")
    ->setScript($script)
    ->addParams("-d : icol 3")
    ->setFileInput($elmFunInput)
    ->setFileExpOutput("{$f}/11-add-col-before.txt");
// Append column to the end (acol)
$tester->createTest()
    ->setName("Append column to the end")
    ->setScript($script)
    ->addParams("-d : acol")
    ->setFileInput($elmFunInput)
    ->setFileExpOutput("{$f}/12-append-col.txt");
// Delete single column (dcol C)
$tester->createTest()
    ->setName("Delete single column")
    ->setScript($script)
    ->addParams("-d : dcol 4")
    ->setFileInput($elmFunInput)
    ->setFileExpOutput("{$f}/13-delete-single-col.txt");
// Delete single column II (dcols C C)
$tester->createTest()
    ->setName("Delete single column II (with dcols)")
    ->setScript($script)
    ->addParams("-d : dcols 4 4")
    ->setFileInput($elmFunInput)
    ->setFileExpOutput("{$f}/13-delete-single-col.txt");
// Delete multiple columns (dcols N M)
$tester->createTest()
    ->setName("Delete multiple columns")
    ->setScript($script)
    ->addParams("-d : dcols 2 4")
    ->setFileInput($elmFunInput)
    ->setFileExpOutput("{$f}/15-delete-multiple-cols.txt");


// BAD INPUTS IN ELEMENTARY FUNCTIONS TESTS
// ========================================
$tester->startNewLevel(2, "Bad inputs in elementary functions", $newLevelCallback);

$elementaryFunctions = [
    "arow" => 0, "acol" => 0, "irow" => 1, "drow" => 1, "icol" => 1, "dcol" => 1, "drows" => 2, "dcols" => 2
];

// Tests: bad number of params, zero value params, negative value params
foreach ($elementaryFunctions as $function => $numberOfParameters) {
    $tester->generateBadInputParamsTests($script, $function, $numberOfParameters);
}


// DATA PROCESSING FUNCTIONS
// =========================
$tester->startNewLevel(3, "Data processing functions", $newLevelCallback);
$dataProcessInput = "{$f}/0-data-process-functions-input.txt";

// Set column value (cset C STR)
$tester->createTest()
    ->setName("Set column value")
    ->setScript($script)
    ->addParams("-d : cset 2 Anonymizováno")
    ->setFileInput($dataProcessInput)
    ->setFileExpOutput("{$f}/17-set-column-value.txt");
// Change column value to lower case (tolower C)
$tester->createTest()
    ->setName("Change column value to lower case (with czech specific letters)")
    ->setScript($script)
    ->addParams("-d : tolower 2")
    ->setFileInput($dataProcessInput)
    ->setFileExpOutput("{$f}/18-column-to-lower-case.txt");
// Change column value to upper case (toupper C)
$tester->createTest()
    ->setName("Change column value to UPPER CASE (with czech specific letters)")
    ->setScript($script)
    ->addParams("-d : toupper 2")
    ->setFileInput($dataProcessInput)
    ->setFileExpOutput("{$f}/19-column-to-upper-case.txt");
// Round number in column to integer (round C)
$tester->createTest()
    ->setName("Round number in column to integer")
    ->setScript($script)
    ->addParams("-d : round 1")
    ->setFileInput("{$f}/0-numbers-input.txt")
    ->setFileExpOutput("{$f}/20-round-number-in-column.txt");
// Remove decimal part from column (int C)
$tester->createTest()
    ->setName("Remove decimal part from column")
    ->setScript($script)
    ->addParams("-d : int 1")
    ->setFileInput("{$f}/0-numbers-input.txt")
    ->setFileExpOutput("{$f}/21-column-remove-dec-part.txt");
// Copy column values to another column (copy N, M)
$tester->createTest()
    ->setName("Copy column values to another column")
    ->setScript($script)
    ->addParams("-d : 3 4")
    ->setFileInput($dataProcessInput)
    ->setFileExpOutput("{$f}/22-copy-val-from-to-column.txt");

// BAD INPUTS IN DATA PROCESSING FUNCTIONS
// =======================================
//$tester->startNewLevel(4, "Bad inputs in data processing functions", $newLevelCallback);


// SELECT FUNCTIONS
// ================
//$tester->startNewLevel(5, "Select functions", $newLevelCallback);


// SCHOOL SAMPLES
// ==============
$tester->startNewLevel(6, "School samples", $newLevelCallback);

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
    ->setFileExpOutput("{$f}/3-fill-week-column.txt")
    ->setRequired(false);
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
    ->setFileInput("{$f}/4-add-points-sum-row.txt") // Follows the previous test
    ->setFileExpOutput("{$f}/5-count-points-sum.txt")
    ->setRequired(false);


// ALL FUNCTIONS (COMBINED) WITH GOOD INPUT
// ========================================
//$tester->startNewLevel(7, "All functions with good input", $newLevelCallback);


// GENERAL BAD INPUTS
// ==================
$tester->startNewLevel(8, "General bad inputs", $newLevelCallback);

// Empty input file
$tester->createTest()
    ->setName("Empty input file")
    ->setScript($script)
    ->setInput("")
    ->setExpExitCode(1);
// Bad delimiters in input
$tester->createTest()
    ->setName("Bad delimiters in input")
    ->setScript($script)
    ->addParams("-d :")
    ->setFileInput("{$f}/0-many-delimiters-input.txt")
    ->setExpExitCode(1);

$tester->runTests($successCallback, $failCallback, $skipCallback);

// Summary report
$successRow = sprintf("Successful tests:\t%d / %d (%d %%)", $tester->getSuccessful(), $tester->getTestsSum(), $tester->getSuccessRate());
$failRow = sprintf("Failed tests:\t\t%d / %d (%d %%)", $tester->getFailed(), $tester->getTestsSum(), $tester->getFailRate());
$skipRow = sprintf("Skipped tests: \t\t%d / %d (%d %%)", $tester->getSkipped(), $tester->getTestsSum(), $tester->getSkipRate());

echo WHITE.str_repeat("=", 39).PHP_EOL;
echo GREEN.$successRow.WHITE.PHP_EOL;
echo RED.$failRow.WHITE.PHP_EOL;
echo WHITE.$skipRow.PHP_EOL;

// Motivational easter eggs
if (($tester->getSuccessRate() + $tester->getSkipRate()) < 50) {
    echo PHP_EOL.RED."\"Hej, tvůj soft... k hovnu.\" - Ivan Vanko".WHITE.PHP_EOL;
}

if ($tester->getSkipRate() > 50) {
    echo PHP_EOL.RED."Hmm, I'm looking at your score... It doesn't sound very well. You should work harder!".WHITE.PHP_EOL;
}

if ($tester->getSuccessRate() === 100) {
    echo PHP_EOL.GREEN."Hooray! You're done! So, you can submit the project and expect many points!".WHITE.PHP_EOL;
}

// Exit code for Gitlab
exit($tester->getFinalExitCode());