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
     * @var Test[] Tests for the try
     */
    private array $tests = [];

    /**
     * @var int Number of successful tests
     */
    private int $successful = 0;
    /**
     * @var int Number of failed tests
     */
    private int $failed = 0;

    /**
     * Creates new test
     *
     * @return Test New test's instance
     */
    public function createTest(): Test
    {
        // Tests are numbered from 1 --> +1
        $number = count($this->tests) + 1;

        return ($this->tests[] = new Test($number));
    }

    /**
     * Tests C script
     *
     * @param callable $successCallback What to call if the test was successful
     * @param callable $failCallback What to call if the test failed
     */
    public function runTests(callable $successCallback, callable $failCallback): void
    {
        foreach ($this->tests as $test) {
            try {
                $this->runIndividualTest($test);

                // Call callback for success tests
                $this->successful++;
                $successCallback($test->getNumber(), $test->getName());
            } catch (ErrorInScriptException $e) {
                $this->failed++;
                $failCallback($e);
            }
        }
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
            $stdOut = []; // It's required because exec() only appends values, not replace

            $command = sprintf("%s %s < %s", $test->getScript(), $paramsGroup[$i], self::TMP_FILE);
            exec($command, $stdOut, $exitCode);

            // Exit codes of all child processes have to be 0
            // Exit code of the end process (C script ran with end parameters) can be different,
            // it depends on expected exit code set up for individual test
            if ($i !== (count($paramsGroup) - 1) && $exitCode !== 0) {
                throw new ErrorInScriptException("Child process ended with error. Returned exit code: \"{$exitCode}\".", $test->getNumber(), $test->getName(), ErrorInScriptException::TYPE_BAD_EXIT_CODE);
            }
        }

        // Output testing
        if(count($stdOut) !== count($expStdOut)) {
            throw new ErrorInScriptException("Number of rows doesn't match.", $test->getNumber(), $test->getName(), ErrorInScriptException::TYPE_BAD_OUTPUT);
        }

        foreach($stdOut as $key => $value){
            if($value != $expStdOut[$key]){
                $errorMessage = sprintf("Output doesn't match (expected: \"%s\", got \"%s\" on line %d).", $expStdOut[$key], $value, $key + 1);
                throw new ErrorInScriptException($errorMessage, $test->getNumber(), $test->getName(), ErrorInScriptException::TYPE_BAD_OUTPUT);
            }
        }

        // Exit code testing
        if ((int)$exitCode !== $test->getExpExitCode()) {
            $errorMessage = sprintf("Exit code doesn't match (expected: \"%s\", got \"%s\").", $test->getExpExitCode(), $exitCode);
            throw new ErrorInScriptException($errorMessage, $test->getNumber(), $test->getName(), ErrorInScriptException::TYPE_BAD_EXIT_CODE);
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
}