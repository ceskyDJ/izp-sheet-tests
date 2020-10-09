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
     * Some valid input for testing exit codes
     */
    private const SOME_INPUT = [
        "Jméno:Příjmení:Částka:Zaplaceno",
        "Michal:Šmahel:100 Kč:9. 10. 2020",
        "Vojtěch:Svědiroh:100 Kč:9. 10. 2020"
    ];
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
                if ($test->isRequired()) {
                    $this->failed++;
                } else {
                    $this->skipped++;
                }

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
     * Generates set of tests with providing bad input parameters to specific function
     *
     * @param string $script Script path
     * @param string $function Function to test
     * @param int $numberOfParams Number of real parameters of the function (default: 0)
     * @param bool $checkZero Do you want to check zero value parameter(s)?
     * @param bool $checkNegative Do you want to check negative value parameter(s)?
     * @param bool $checkFloatingPoint Do you want to check floating point number parameter(s)?
     */
    public function generateBadInputParamsTests(
        string $script,
        string $function,
        int $numberOfParams = 0,
        bool $checkZero = true,
        bool $checkNegative = true,
        bool $checkFloatingPoint = true
    ): void {
        // Bad number of parameters
        $params = "";
        $fakeNumOfParams = rand($numberOfParams + 1, $numberOfParams + 5);
        for ($i = 0; $i < $fakeNumOfParams; $i++) {
            $params .= " ".rand(1, 20);
        }

        $this->createTest()
            ->setName("{$function} with bad number of parameters")
            ->setScript($script)
            ->addParams("-d : {$function}{$params}")
            ->setStdIn(self::SOME_INPUT)
            ->setExpExitCode(1);

        // Zero value parameter(s)
        if ($numberOfParams === 0 || !$checkZero) {
            return;
        }

        $params = str_repeat(" 0", $numberOfParams);

        $this->createTest()
            ->setName("{$function} with zero value parameter(s)")
            ->setScript($script)
            ->addParams("-d : {$function}{$params}")
            ->setStdIn(self::SOME_INPUT)
            ->setExpExitCode(1);

        // Negative value parameter(s)
        if ($numberOfParams === 0 || !$checkNegative) {
            return;
        }

        $params = "";
        for ($i = 0; $i < $numberOfParams; $i++) {
            $params .= " ".rand(-20, -1);
        }

        $this->createTest()
            ->setName("{$function} with negative value parameter(s)")
            ->setScript($script)
            ->addParams("-d : {$function}{$params}")
            ->setStdIn(self::SOME_INPUT)
            ->setExpExitCode(1);

        // Floating point number parameter(s)
        if ($numberOfParams === 0 || !$checkFloatingPoint) {
            return;
        }

        $params = "";
        for ($i = 0; $i < $numberOfParams; $i++) {
            $params .= " ".rand(1, 99) / 100;
        }

        $this->createTest()
            ->setName("{$function} with floating point number parameter(s)")
            ->setScript($script)
            ->addParams("-d : {$function}{$params}")
            ->setStdIn(self::SOME_INPUT)
            ->setExpExitCode(1);
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

        // Exit code testing
        if ((int)$exitCode !== $test->getExpExitCode()) {
            $errorMessage = sprintf("Exit code doesn't match (expected: \"%s\", got \"%s\").", $test->getExpExitCode(), $exitCode);
            throw new ErrorInScriptException($errorMessage, $test->getNumber(), $test->getName(), ErrorInScriptException::TYPE_BAD_EXIT_CODE);
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
}