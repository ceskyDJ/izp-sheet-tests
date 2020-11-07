<?php

declare(strict_types=1);

/**
 * Tester for C scripts
 *
 * Tests C scripts by arguments provided in params of public test* methods.
 *
 * @author Michal ŠMAHEL <admin@ceskydj.cz>
 * @author Vojtěch SVĚDIROH
 * @date October 2020
 * @noinspection PhpIllegalPsrClassPathInspection
 */
class Tester
{
    /**
     * Default exit code in case the C script return nothing or fail
     */
    private const DEFAULT_EXIT_CODE = 9999;
    /**
     * Temporary file for simulate production conditions
     */
    private const TMP_FILE = "tmp/sample.csv";

    /**
     * Successful test flag
     */
    private const SUCCESSFUL = 0;
    /**
     * Failed test flag
     */
    private const FAILED = 1;
    /**
     * Skipped test flag
     */
    private const SKIPPED = 2;

    /**
     * @var Test[] Tests for the try
     */
    private array $tests = [];
    /**
     * @var array Difficulty levels
     */
    private array $levels = [];
    /**
     * @var bool Should be all new tests set as non-required (skipped if fail)?
     */
    private bool $autoSkip = false;

    /**
     * @var int Number of successful tests
     */
    private int $successful = 0;
    /**
     * @var int Number of failed tests
     */
    private int $failed = 0;
    /**
     * @var int Number of skipped non-required test with fail result
     */
    private int $skipped = 0;

    /**
     * Creates new test
     *
     * @return Test New test's instance
     */
    public function createTest(): Test
    {
        // Tests are numbered from 1 --> +1
        $number = count($this->tests) + 1;

        $test = $this->tests[] = (new Test($number))
            ->setLevel($this->getActualLevel());

        // Set non-required state if auto skipping is enabled
        if ($this->autoSkip) {
            $test->setRequired(false);
        }

        return $test;
    }

    /**
     * Tests C script
     *
     * @param callable $successCallback What to call if the test was successful
     * @param callable $failCallback What to call if the test failed
     * @param callable $skipCallback What to call if the test was skipped
     * @param callable $unreachedLevelCallback What to call if the level contains only skipped tests (=> was unreached)
     * @param bool $verboseOutput Activate verbose output? (result for every individual test)
     */
    public function runTests(callable $successCallback, callable $failCallback, callable $skipCallback, callable $unreachedLevelCallback, bool $verboseOutput): void
    {
        $level = -1;
        $callbackBuffer = []; // All callbacks from actual level
        foreach ($this->tests as $key => $test) {
            if ($test->getLevel() > $level) {
                $this->activateNextLevel($level = $test->getLevel());
            }

            try {
                $this->runIndividualTest($test);

                // Call callback for success tests
                $this->successful++;
                $callbackBuffer[] = [
                    'type' => self::SUCCESSFUL,
                    'callback' => $successCallback,
                    'params' => [$test->getNumber(), $test->getName()]
                ];
            } catch (ErrorInScriptException $e) {
                if (!$this->autoSkip && $test->isRequired()) {
                    $this->failed++;
                    $callbackBuffer[] = [
                        'type' => self::FAILED,
                        'callback' => $failCallback,
                        'params' => [$e]
                    ];
                } else {
                    $this->skipped++;
                    $callbackBuffer[] = [
                        'type' => self::SKIPPED,
                        'callback' => $skipCallback,
                        'params' => [$test->getNumber(), $test->getName()]
                    ];
                }
            }

            // Report of the level
            // Only run when actual test is the last of the level
            if ($level >= 0 && (!isset($this->tests[$key + 1]) || $this->tests[$key + 1]->getLevel() > $level)) {
                $this->processTestCallbacks($callbackBuffer, $unreachedLevelCallback, $verboseOutput);
                $callbackBuffer = []; // Empty for the next level
            }
        }
    }

    /**
     * Starts new difficulty level
     *
     * @param int $level Level number
     * @param string $name Level name
     * @param callable $callback Something to call when the new level starts
     */
    public function startNewLevel(int $level, string $name, callable $callback): void
    {
        // Level number has to be unique
        if (key_exists($level, $this->levels)) {
            throw new Error(sprintf(
                "Level number is unique. You use %d twice (for %s and %s).",
                $level,
                $this->levels[$level]['name'],
                $name
            ));
        }

        $this->levels[$level] = [
            'name' => $name,
            'callback' => $callback
        ];
    }

    /**
     * Runs individual test
     *
     * @param Test $test Test to run
     *
     * @throws \ErrorInScriptException The test failed
     */
    private function runIndividualTest(Test $test): void
    {
        // Output things
        // The first child test needs input in $stdOut like other tests
        // The general reason for that is child tests are chaining and need input and output in one variable
        $stdOut = $test->getStdIn();
        $exitCode = self::DEFAULT_EXIT_CODE;

        // Test things
        $expStdOut = $test->getExpStdOut();

        $paramsGroup = $test->getParamsGroup();
        for ($i = 0; $i < count($paramsGroup); $i++) {
            $this->prepareTestFile(self::TMP_FILE, $stdOut);
            $devNull = null; // Simulates /dev/null - output from exec() would be deleted
            $command = sprintf("%s %s < %s 2> /dev/null", $test->getScript(), $paramsGroup[$i], self::TMP_FILE);

            // Get exit code
            exec($command, $devNull, $exitCode);
            // Get output
            if (($commandOutput = shell_exec($command)) !== null) {
                $stdOut = explode("\n", $commandOutput);
            } else {
                $stdOut = [];
            }

            // Exit codes of all child processes have to be 0
            // Exit code of the end process (C script ran with end parameters) can be different,
            // it depends on expected exit code set up for individual test
            if ($i !== (count($paramsGroup) - 1) && $exitCode !== 0) {
                throw new ErrorInScriptException("Child process ended with error. Returned exit code: \"{$exitCode}\".", $test, ErrorInScriptException::TYPE_BAD_EXIT_CODE);
            }
        }

        // Exit code testing
        if ((int)$exitCode !== $test->getExpExitCode()) {
            $errorMessage = sprintf("Exit code doesn't match (expected: \"%s\", got \"%s\").", $test->getExpExitCode(), $exitCode);
            throw new ErrorInScriptException($errorMessage, $test, ErrorInScriptException::TYPE_BAD_EXIT_CODE);
        }

        // Output testing
        if(count($stdOut) !== count($expStdOut)) {
            $errorMessage = sprintf("Number of rows doesn't match (expected: %d, got %d).", count($expStdOut), count($stdOut));
            throw new ErrorInScriptException($errorMessage, $test, ErrorInScriptException::TYPE_BAD_OUTPUT);
        }

        foreach($stdOut as $key => $value){
            if($value != $expStdOut[$key]){
                $errorMessage = sprintf("Output doesn't match (expected: \"%s\", got \"%s\" on line %d).", $expStdOut[$key], $value, $key + 1);
                throw new ErrorInScriptException($errorMessage, $test, ErrorInScriptException::TYPE_BAD_OUTPUT);
            }
        }
    }

    /**
     * Activates the next difficualty level
     *
     * @param int $nextLevel Next level's number
     */
    private function activateNextLevel(int $nextLevel): void
    {
        // Callback before next level
        $this->levels[$nextLevel]['callback']($nextLevel, $this->levels[$nextLevel]['name']);

        // If auto skip is enabled, there is no sense to regulate tests
        if ($this->autoSkip) {
            return;
        }

        // If low-level tests weren't successful, automatically skip the next one
        if ($this->failed !== 0) {
            $this->setAutoSkip(true);
        }
    }

    /**
     * Process callbacks in the callback buffer for individual level
     * If there is all skip callbacks in the buffer, it will only call general skip callback (for level)
     *
     * @param array $callbackBuffer Buffer of callbacks to process
     * @param callable $unreachedLevelCallback What to call if there is the buffer full of the skip callbacks
     * @param bool $verboseOutput Activate verbose output? (result for every individual test)
     */
    private function processTestCallbacks(array $callbackBuffer, callable $unreachedLevelCallback, bool $verboseOutput): void
    {
        // If verbose output was activated, only simply print results for each test
        if ($verboseOutput === true) {
            $this->callTestCallbacks($callbackBuffer);

            return;
        }

        // Search callbacks with other types than SKIPPED
        // If some is found, unreached is set to false --> this level is reached
        $unreached = array_reduce(
            $callbackBuffer,
            fn($unreached, $item) => $item['type'] !== self::SKIPPED ? false : $unreached,
            true
        );

        // Unreached levels have only information callback
        if($unreached === true) {
            $unreachedLevelCallback();

            return;
        }

        // Reached levels have all individual result callbacks
        $this->callTestCallbacks($callbackBuffer);
    }

    /**
     * Calls all callbacks from the callback buffer
     *
     * @param array $callbackBuffer Buffer of callbacks to call
     */
    private function callTestCallbacks(array $callbackBuffer): void
    {
        foreach ($callbackBuffer as $callback) {
            call_user_func_array($callback['callback'], $callback['params']);
        }
    }

    /**
     * Prepares test CSV file (input for the C script)
     *
     * @param string $name File name
     * @param array $content File content
     *
     * @return string Absolute path to the file
     */
    private function prepareTestFile(string $name, array $content): string
    {
        file_put_contents($name, implode(PHP_EOL, $content));

        return $this->getRealPath($name);
    }

    /**
     * Returns real (absolute) path from the relative one
     *
     * @param string $relativePath Relative path
     *
     * @return string Real (absolute) path
     */
    private function getRealPath(string $relativePath): string
    {
        return str_replace(" ", "\ ", realpath($relativePath));
    }

    /**
     * Returns actual test's difficulty level
     *
     * @return int Actual test's difficulty level
     */
    private function getActualLevel(): int
    {
        return (int)array_key_last($this->levels);
    }

    /**
     * Returns final exit code depends on number of failed tests
     *
     * @return int Final exit code to return as result of testing
     */
    public function getFinalExitCode(): int
    {
        // 254 is the biggest usable exit code
        return ($this->failed <= 254 ? $this->failed : 254);
    }

    /**
     * Returns number of created tests
     *
     * @return int Number of created tests
     */
    public function getTestsSum(): int
    {
        return count($this->tests);
    }

    /**
     * Returns summary success rate
     *
     * @return int Summary success rate
     */
    public function getSuccessRate(): int
    {
        // Fix for division by zero error
        if ($this->getTestsSum() === 0) {
            return 0;
        }

        return (int)round(($this->successful / $this->getTestsSum()) * 100, 0);
    }

    /**
     * Returns summary fail rate
     *
     * @return int Summary fail rate
     */
    public function getFailRate(): int
    {
        // Fix for division by zero error
        if ($this->getTestsSum() === 0) {
            return 0;
        }

        return (int)round(($this->failed / $this->getTestsSum()) * 100, 0);
    }

    /**
     * Returns summary skip rate
     *
     * @return int Summary skip rate
     */
    public function getSkipRate(): int
    {
        // Fix for division by zero error
        if ($this->getTestsSum() === 0) {
            return 0;
        }

        return (int)round(($this->skipped / $this->getTestsSum()) * 100, 0);
    }

    /**
     * Getter for number of successful tests
     *
     * @return int Number of successful tests
     */
    public function getSuccessful(): int
    {
        return $this->successful;
    }

    /**
     * Getter for number of failed tests
     *
     * @return int Number of failed tests
     */
    public function getFailed(): int
    {
        return $this->failed;
    }

    /**
     * Getter for number of skipped tests
     *
     * Skip test is the test with fail status and required set to false
     *
     * @return int Number of skipped tests
     */
    public function getSkipped(): int
    {
        return $this->skipped;
    }

    /**
     * Setter for auto skip state
     *
     * @param bool $autoSkip Should be all newly created tests set as non required?
     */
    public function setAutoSkip(bool $autoSkip): void
    {
        $this->autoSkip = $autoSkip;
    }
}
