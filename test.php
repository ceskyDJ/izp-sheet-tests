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
// test.php -v          Activate verbose output mode (all individual tests' results will be printed)
$args = getopt("cv", ["ext-color", "verbose"]);
$verboseOutput = key_exists("v", $args) || key_exists("verbose", $args);

// Colors for terminal outputs
if (key_exists("c", $args) || key_exists("ext-color", $args)) {
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

require "classes/Flags.php";
require "classes/ErrorInScriptException.php";
require "classes/Test.php";
require "classes/Tester.php";
require "classes/TestGenerator.php";

$tester = new Tester();
$generator = new TestGenerator($tester);
$script = "tmp/sheet"; // There is no extension in GNU/Linux OSes, so it's correct
$f = "files";

// Callbacks for auto-generated output
$newLevelCallback = function (int $level, string $name) {
    $message = "<< Started level {$level} ({$name}) >>";

    if ($level > 0) {
        echo WHITE.str_repeat("-", mb_strlen($message)).PHP_EOL;
    }
    echo YELLOW.$message.WHITE.PHP_EOL;
};
$successCallback = function (int $number, string $name) {
    echo GREEN."[{$number}] {$name}: The test was successful.".WHITE.PHP_EOL;
};
$failCallback = function (ErrorInScriptException $e) {
    $type = $e->getType() === ErrorInScriptException::TYPE_BAD_OUTPUT ? "Output error" : "Exit code error";
    $test = $e->getTest();
    echo RED."[{$test->getNumber()}] {$test->getName()}: {$type} - {$e->getMessage()}".WHITE.PHP_EOL;
    echo RED."\t{$test->getTestedCommands()}".WHITE.PHP_EOL;
};
$skipCallback = function (int $number, string $name) {
    echo WHITE."[{$number}] {$name}: The test was skipped.".PHP_EOL;
};
$unreachedLevel = function () {
    echo WHITE."You haven't reached this level (all tests of this level were skipped).".PHP_EOL;
};

// STANDARD BEHAVIOUR
// ==================
$tester->startNewLevel(0, "Standard behaviour", $newLevelCallback);

// Simple call
$tester->createTest()
    ->setName("Simple call without parameters (=> without changes)")
    ->setScript($script)
    ->addParams("-d :")
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


// BAD INPUTS IN ELEMENTARY FUNCTIONS
// ==================================
$tester->startNewLevel(2, "Bad inputs in elementary functions", $newLevelCallback);

$elementaryFunctions = [
    'arow'  => [],
    'acol'  => [],
    'irow'  => [Flags::STD_INT],
    'drow'  => [Flags::STD_INT],
    'icol'  => [Flags::STD_INT],
    'dcol'  => [Flags::STD_INT],
    'drows' => [Flags::STD_INT | Flags::SMALLER, Flags::STD_INT | Flags::BIGGER],
    'dcols' => [Flags::STD_INT | Flags::SMALLER, Flags::STD_INT | Flags::BIGGER]
];

foreach ($elementaryFunctions as $function => $argsWithFlags) {
    $generator->generateBadInputParamsTests($script, $function, $argsWithFlags);
}


// DATA PROCESSING FUNCTIONS
// =========================
$tester->startNewLevel(3, "Data processing functions", $newLevelCallback);
$dataProcessInput = "{$f}/0-data-process-functions-input.txt";
$numOnlyInput = "{$f}/0-numeric-only-input.txt";

// Set column value (cset C STR)
$tester->createTest()
    ->setName("Set column value")
    ->setScript($script)
    ->addParams("-d : cset 2 Anonymizováno")
    ->setFileInput($dataProcessInput)
    ->setFileExpOutput("{$f}/17-set-column-value.txt");
// Change column value to lower case (tolower C)
$tester->createTest()
    ->setName("Change column value to lower case")
    ->setScript($script)
    ->addParams("-d : tolower 2")
    ->setFileInput("{$f}/0-change-case-input.txt")
    ->setFileExpOutput("{$f}/18-column-to-lower-case.txt");
// Change column value to upper case (toupper C)
$tester->createTest()
    ->setName("Change column value to UPPER CASE")
    ->setScript($script)
    ->addParams("-d : toupper 2")
    ->setFileInput("{$f}/0-change-case-input.txt")
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
// Copy column values to another column (copy N M)
$tester->createTest()
    ->setName("Copy column values to another column")
    ->setScript($script)
    ->addParams("-d : copy 3 4")
    ->setFileInput($dataProcessInput)
    ->setFileExpOutput("{$f}/22-copy-val-from-to-column.txt");
// Swap values between columns (swap N M)
$tester->createTest()
    ->setName("Swap values between columns")
    ->setScript($script)
    ->addParams("-d : swap 1 2")
    ->setFileInput($dataProcessInput)
    ->setFileExpOutput("{$f}/23-swap-values-between-cols.txt");
// Move column before another column (move N M)
$tester->createTest()
    ->setName("Move column before another column")
    ->setScript($script)
    ->addParams("-d : move 4 1")
    ->setFileInput($dataProcessInput)
    ->setFileExpOutput("{$f}/24-move-col-before-col.txt");
// Count summary values of selected columns (csum C N M)
$tester->createTest()
    ->setName("Count summary values of selected columns")
    ->setScript($script)
    ->addParams("-d : csum 4 1 3")
    ->setFileInput($numOnlyInput)
    ->setFileExpOutput("{$f}/25-sel-cols-sum.txt")
    ->setRequired(false);
// Count arithmetic average of selected columns (cavg C N M)
$tester->createTest()
    ->setName("Count arithmetic average of selected columns")
    ->setScript($script)
    ->addParams("-d : cavg 4 1 3")
    ->setFileInput($numOnlyInput)
    ->setFileExpOutput("{$f}/26-sel-cols-avg.txt")
    ->setRequired(false);
// Find minimal value of selected columns (cmin C N M)
$tester->createTest()
    ->setName("Find minimal value of selected columns")
    ->setScript($script)
    ->addParams("-d : cmin 4 1 3")
    ->setFileInput($numOnlyInput)
    ->setFileExpOutput("{$f}/27-sel-cols-min.txt")
    ->setRequired(false);
// Find maximum value of selected columns (cmax C N M)
$tester->createTest()
    ->setName("Find MAXIMUM value of selected columns")
    ->setScript($script)
    ->addParams("-d : cmax 4 1 3")
    ->setFileInput($numOnlyInput)
    ->setFileExpOutput("{$f}/28-sel-cols-max.txt")
    ->setRequired(false);
// Count number of non-empty values of selected columns (ccount C N M)
$tester->createTest()
    ->setName("Count number of non-empty values of selected columns")
    ->setScript($script)
    ->addParams("-d : ccount 6 1 5")
    ->setFileInput("{$f}/0-some-empty-cols-input.txt")
    ->setFileExpOutput("{$f}/29-sel-cols-count.txt")
    ->setRequired(false);
// Insert numerical sequence to selected columns (cseq N M B)
$tester->createTest()
    ->setName("Insert numerical sequence to selected columns")
    ->setScript($script)
    ->addParams("-d : cseq 1 3 121")
    ->setFileInput("{$f}/0-empty-cols-input.txt")
    ->setFileExpOutput("{$f}/30-sel-cols-num-seq.txt")
    ->setRequired(false);
// Insert numerical sequence to column of selected rows (rseq C N M B)
$tester->createTest()
    ->setName("Insert numerical sequence to column of selected rows")
    ->setScript($script)
    ->addParams("-d : rseq 1 1 - 555")
    ->setFileInput("{$f}/0-some-empty-cols-input.txt")
    ->setFileExpOutput("{$f}/31-sel-rows-num-seq.txt")
    ->setRequired(false);
// Count column summary of selected rows (rsum C N M)
$tester->createTest()
    ->setName("Count column summary of selected rows")
    ->setScript($script)
    ->addParams("-d : rsum 1 1 5")
    ->setFileInput($numOnlyInput)
    ->setFileExpOutput("{$f}/32-sel-rows-sum.txt")
    ->setRequired(false);
// Count column arithmetic average of selected rows (ravg C N M)
$tester->createTest()
    ->setName("Count column arithmetic average of selected rows")
    ->setScript($script)
    ->addParams("-d : ravg 1 1 5")
    ->setFileInput($numOnlyInput)
    ->setFileExpOutput("{$f}/33-sel-rows-avg.txt")
    ->setRequired(false);
// Find column minimal value of selected rows (rmin C N M)
$tester->createTest()
    ->setName("Find column minimal value of selected rows")
    ->setScript($script)
    ->addParams("-d : rmin 1 1 5")
    ->setFileInput($numOnlyInput)
    ->setFileExpOutput("{$f}/34-sel-rows-min.txt")
    ->setRequired(false);
// Find column MAXIMAL value of selected rows (rmax C N M)
$tester->createTest()
    ->setName("Find column MAXIMAL value of selected rows")
    ->setScript($script)
    ->addParams("-d : rmax 1 1 5")
    ->setFileInput($numOnlyInput)
    ->setFileExpOutput("{$f}/35-sel-rows-max.txt")
    ->setRequired(false);
// Count column number of non-empty values of selected rows (rcount C N M)
$tester->createTest()
    ->setName("Count column number of non-empty values of selected rows")
    ->setScript($script)
    ->addParams("-d : rcount 2 1 6")
    ->setFileInput("{$f}/0-some-empty-cols-input.txt")
    ->setFileExpOutput("{$f}/36-sel-rows-count.txt")
    ->setRequired(false);


// BAD INPUTS IN DATA PROCESSING FUNCTIONS
// =======================================
$tester->startNewLevel(4, "Bad inputs in data processing functions", $newLevelCallback);

$dataProcessingFunctions = [
    'cset' => [Flags::STD_INT, Flags::STRING],
    'tolower' => [Flags::STD_INT],
    'toupper' => [Flags::STD_INT],
    'round' => [Flags::STD_INT],
    'int' => [Flags::STD_INT],
    'copy' => [Flags::STD_INT, Flags::STD_INT],
    'swap' => [Flags::STD_INT, Flags::STD_INT],
    'move' => [Flags::STD_INT, Flags::STD_INT],
    'csum' => [Flags::STD_INT | Flags::OUT, Flags::STD_INT | Flags::SMALLER, Flags::STD_INT | Flags::BIGGER],
    'cavg' => [Flags::STD_INT | Flags::OUT, Flags::STD_INT | Flags::SMALLER, Flags::STD_INT | Flags::BIGGER],
    'cmin' => [Flags::STD_INT | Flags::OUT, Flags::STD_INT | Flags::SMALLER, Flags::STD_INT | Flags::BIGGER],
    'cmax' => [Flags::STD_INT | Flags::OUT, Flags::STD_INT | Flags::SMALLER, Flags::STD_INT | Flags::BIGGER],
    'ccount' => [Flags::STD_INT | Flags::OUT, Flags::STD_INT | Flags::SMALLER, Flags::STD_INT | Flags::BIGGER],
    'cseq' => [Flags::STD_INT | Flags::SMALLER, Flags::STD_INT | Flags::BIGGER, Flags::STD_INT],
    'rseq' => [Flags::STD_INT, Flags::STD_INT | Flags::SMALLER, Flags::STD_INT | Flags::BIGGER, Flags::STD_INT],
    'rsum' => [Flags::STD_INT, Flags::STD_INT | Flags::SMALLER, Flags::STD_INT | Flags::BIGGER],
    'ravg' => [Flags::STD_INT, Flags::STD_INT | Flags::SMALLER, Flags::STD_INT | Flags::BIGGER],
    'rmin' => [Flags::STD_INT, Flags::STD_INT | Flags::SMALLER, Flags::STD_INT | Flags::BIGGER],
    'rmax' => [Flags::STD_INT, Flags::STD_INT | Flags::SMALLER, Flags::STD_INT | Flags::BIGGER],
    'rcount' => [Flags::STD_INT, Flags::STD_INT | Flags::SMALLER, Flags::STD_INT | Flags::BIGGER],
];

foreach ($dataProcessingFunctions as $function => $argsWithFlags) {
    $generator->generateBadInputParamsTests($script, $function, $argsWithFlags);
}


// SELECT FUNCTIONS
// ================
$tester->startNewLevel(5, "Select functions", $newLevelCallback);

// Apply function on selected rows (rows N M)
$tester->createTest()
    ->setName("Apply function on selected rows")
    ->setScript($script)
    ->addParams("-d : rows 2 - toupper 2")
    ->setFileInput("{$f}/0-change-case-input.txt")
    ->setFileExpOutput("{$f}/37-upper-case-surnames.txt");
// Apply function on rows that have column begins with something (beginswith C STR)
$tester->createTest()
    ->setName("Apply function to rows that have columns begins with something")
    ->setScript($script)
    ->addParams("-d : beginswith 1 - cset 1 0")
    ->setFileInput($numOnlyInput)
    ->setFileExpOutput("{$f}/38-set-neg-nums-to-zero.txt");
// Apply function on rows that have column contains something (contains C STR)
$tester->createTest()
    ->setName("Apply function on rows that have columns contains something")
    ->setScript($script)
    ->addParams("-d : contains 1 . int 1")
    ->setFileInput($numOnlyInput)
    ->setFileExpOutput("{$f}/39-remove-dec-part.txt");


// BAD INPUTS IN SELECT FUNCTIONS
// ==============================
$tester->startNewLevel(6, "Bad inputs in select functions", $newLevelCallback);

$selectFunctions = [
    'rows' => [Flags::STD_INT | Flags::SMALLER, Flags::STD_INT | Flags::BIGGER],
    'beginswith' => [Flags::STD_INT, Flags::STRING],
    'contains' => [Flags::STD_INT, Flags::STRING]
];

foreach ($selectFunctions as $function => $argsWithFlags) {
    $generator->generateBadInputParamsTests($script, $function, $argsWithFlags);
}


// SCHOOL SAMPLES
// ==============
$tester->startNewLevel(7, "School samples", $newLevelCallback);

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
$tester->startNewLevel(8, "All functions with good input", $newLevelCallback);

// Add and append new rows
$tester->createTest()
    ->setName("Add and append new rows")
    ->setScript($script)
    ->addParams("-d : arow irow 4")
    ->setFileInput($dataProcessInput)
    ->setFileExpOutput("{$f}/40-add-some-new-rows.txt");
// Delete column and row
$tester->createTest()
    ->setName("Delete column and row")
    ->setScript($script)
    ->addParams("-d : drow 9 dcol 1")
    ->setFileInput($dataProcessInput)
    ->setFileExpOutput("{$f}/41-del-row-and-col.txt");


// GENERAL BAD INPUTS
// ==================
$tester->startNewLevel(9, "General bad inputs", $newLevelCallback);

// Empty input file
$tester->createTest()
    ->setName("Empty input file")
    ->setScript($script)
    ->setInput("")
    ->setExpExitCode(1);


// Get data
ob_start();
$tester->runTests($successCallback, $failCallback, $skipCallback, $unreachedLevel, $verboseOutput);
$testResults = ob_get_clean();

$testerName = "Sheet.c - Tester";

$successRow = sprintf("Successful tests:\t%d / %d (%d %%)", $tester->getSuccessful(), $tester->getTestsSum(), $tester->getSuccessRate());
$failRow = sprintf(   "Failed tests:    \t%d / %d (%d %%)", $tester->getFailed(), $tester->getTestsSum(), $tester->getFailRate());
$skipRow = sprintf(   "Skipped tests:   \t%d / %d (%d %%)", $tester->getSkipped(), $tester->getTestsSum(), $tester->getSkipRate());

// Print report
echo GREEN."+".str_repeat("-", strlen($testerName) + 2)."+".WHITE.PHP_EOL;
echo GREEN."+ ".$testerName." +".WHITE.PHP_EOL;
echo GREEN."+".str_repeat("-", strlen($testerName) + 2)."+".WHITE.PHP_EOL.PHP_EOL;

echo $testResults.PHP_EOL;

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