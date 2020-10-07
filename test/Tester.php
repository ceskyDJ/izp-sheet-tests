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
    private const TMP_FILE = __DIR__."/../tmp/sample.csv";

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

            $command = sprintf("cat %s | %s %s", self::TMP_FILE, $test->getScript(), $paramsGroup[$i]);
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

/**
 * Entity for test
 *
 * It's used for storing test's data and basic operations.
 *
 * @author Michal ŠMAHEL <admin@ceskydj.cz>
 * @date October 2020
 */
class Test
{
    /**
     * @var int Test's number (position in testing)
     */
    private int $number;
    /**
     * @var string Test's name
     */
    private string $name;
    /**
     * @var string Path to compiled C script to run test on
     */
    private string $script;
    /**
     * @var array Group of parameters provided to the C script
     */
    private array $paramsGroup = [];
    /**
     * @var array Input data for the C script
     */
    private array $stdIn;
    /**
     * @var array Expected output data from the C script
     */
    private array $expStdOut;
    /**
     * @var int Expected exit code returned by the C script
     */
    private int $expExit = 0;

    /**
     * Test constructor
     *
     * @param int $number
     */
    public function __construct(int $number)
    {
        $this->number = $number;
    }

    /**
     * Getter for test's number
     *
     * @return int Test's number
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * Getter for test's name
     *
     * @return string Test's name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Fluent setter for test's name
     *
     * @param string $name Test's name
     *
     * @return Test Test's instance
     */
    public function setName(string $name): Test
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Getter for script path
     *
     * @return string Script path
     */
    public function getScript(): string
    {
        return $this->script;
    }

    /**
     * Setter for script path
     *
     * @param string $script Script path or script name (if in the same folder)
     *
     * @return Test Test's instance
     */
    public function setScript(string $script): Test
    {
        $this->script = strstr($script, "/") ? $script : "./{$script}";;

        return $this;
    }

    /**
     * Getter for group of parameters for the C script
     *
     * @return array Group of parameters for the C script
     */
    public function getParamsGroup(): array
    {
        // This is simple test without child processes and without parameters
        if (empty($this->paramsGroup)) {
            return [""];
        }

        return $this->paramsGroup;
    }

    /**
     * Adds parameters for the C script to group of all parameters
     *
     * @param string $params Parameters for the C script
     *
     * @return Test Test's instance
     */
    public function addParams(string $params): Test
    {
        $this->paramsGroup[] = $params;

        return $this;
    }

    /**
     * Getter for input data for the C script
     *
     * @return array Input data for the C script
     */
    public function getStdIn(): array
    {
        return $this->stdIn;
    }

    /**
     * Setter for input data for the C script
     *
     * @param array $stdIn Input data for the C script (array of rows)
     *
     * @return Test Test's instance
     */
    public function setStdIn(array $stdIn): Test
    {
        $this->stdIn = $stdIn;

        return $this;
    }

    /**
     * Sets input data for the C script from string
     *
     * @param string $input Input data for the C script in raw string form
     *
     * @return Test Test's instance
     */
    public function setInput(string $input): Test
    {
        $this->stdIn = explode("\n", $input);

        return $this;
    }

    /**
     * Sets input data for the C script from file
     *
     * @param string $file Path to file with input data for the C script
     *
     * @return Test Test's instance
     */
    public function setFileInput(string $file): Test
    {
        $this->stdIn = explode("\n", file_get_contents($file));

        return $this;
    }

    /**
     * Getter for expected output from the C script
     *
     * @return array Expected output from the C script
     */
    public function getExpStdOut(): array
    {
        return $this->expStdOut;
    }

    /**
     * Setter for expected output from the C script
     *
     * @param array $expStdOut Expected output from the C script (array of rows)
     *
     * @return Test Test's instance
     */
    public function setExpStdOut(array $expStdOut): Test
    {
        $this->expStdOut = $expStdOut;

        return $this;
    }

    /**
     * Sets expected output from the C script from string
     *
     * @param string $expOutput Expected output from the C script in raw string form
     *
     * @return Test Test's instance
     */
    public function setExpOutput(string $expOutput): Test
    {
        $this->expStdOut = explode("\n", $expOutput);

        return $this;
    }

    /**
     * Sets expected output from the C script from file
     *
     * @param string $file Path to file with expected output from the C script
     *
     * @return Test Test's instance
     */
    public function setFileExpOutput(string $file): Test
    {
        $this->expStdOut = explode("\n", file_get_contents($file));

        return $this;
    }

    /**
     * Getter for expected exit code returned from the C script
     *
     * @return int Expected exit code returned from the C script
     */
    public function getExpExitCode(): int
    {
        return $this->expExit;
    }

    /**
     * Setter for the expected exit code returned from the C script
     *
     * @param int $expExit Expected exit code returned from the C script
     *
     * @return Test Test's instance
     */
    public function setExpExitCode(int $expExit): Test
    {
        $this->expExit = $expExit;

        return $this;
    }
}