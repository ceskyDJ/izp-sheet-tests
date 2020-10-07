<?php

declare(strict_types=1);

class Tester
{
    /**
     * Default exit code in case the C script return nothing or fail
     */
    private const DEFAULT_EXIT_CODE = 9999;

    /**
     * @var int Number of ran tests
     */
    private int $ran = 0;
    /**
     * @var int Number of successful tests
     */
    private int $successful = 0;
    /**
     * @var int Number of failed tests
     */
    private int $failed = 0;

    /**
     * Tests C script
     *
     * @param string $name Test's name
     * @param string $script Path to the C script (or its name)
     * @param string $params Parameters for the C script
     * @param array $stdIn Input for the C script
     * @param array $expStdOut Expected output returned by the C script
     * @param int $expExit Expected exit code returned by the C script
     * @param callable $callback What to call if the test was successful
     *
     * @throws \ErrorInScriptException The test failed
     */
    public function test(string $name, string $script, string $params, array $stdIn, array $expStdOut, int $expExit, callable $callback): void
    {
        $this->ran++;

        // Input things
        $testFile = $this->prepareTestFile(__DIR__."/../tmp/sample.csv", $stdIn);
        $script = strstr($script, "/") ? $this->getRealPath($script) : "./{$script}";

        // Output things
        $stdOut = [];
        $exitCode = self::DEFAULT_EXIT_CODE;

        exec("cat {$testFile} | {$script} {$params}", $stdOut, $exitCode);

        if(count($stdOut) !== count($expStdOut)) {
            $this->failed++;
            throw new ErrorInScriptException("Number of rows doesn't match", $this->ran, $name, ErrorInScriptException::TYPE_BAD_OUTPUT);
        }

        foreach($stdOut as $key => $value){
            if($value != $expStdOut[$key]){
                $this->failed++;
                $errorMessage = sprintf("Output doesn't match (expected: \"%s\", got \"%s\" on line %d).", $expStdOut[$key], $value, $key + 1);
                throw new ErrorInScriptException($errorMessage, $this->ran, $name, ErrorInScriptException::TYPE_BAD_OUTPUT);
            }
        }

        if ((int)$exitCode !== $expExit) {
            $this->failed++;
            throw new ErrorInScriptException("Exit code doesn't match", $this->ran, $name, ErrorInScriptException::TYPE_BAD_ERROR_CODE);
        }

        // Call callback for success tests
        $this->successful++;
        $callback($this->ran, $name);
    }

    /**
     * Tests C script with raw string inputs
     *
     * @param string $name Test's name
     * @param string $script Path to the C script (or its name)
     * @param string $params Parameters for the C script
     * @param string $input String with input for the C script
     * @param string $expOutput String with expected output returned by the C script
     * @param int $expExit Expected exit code returned by the C script
     * @param callable $callback What to call if the test was successful
     *
     * @throws \ErrorInScriptException The test failed
     */
    public function testStringInput(string $name, string $script, string $params, string $input, string $expOutput, int $expExit, callable $callback): void
    {
        $stdIn = explode("\n", $input);
        $expStdOut = explode("\n", $expOutput);

        $this->test($name, $script, $params, $stdIn, $expStdOut, $expExit, $callback);
    }

    /**
     * Tests C script with file inputs
     *
     * @param string $name Test's name
     * @param string $script Path to the C script (or its name)
     * @param string $params Parameters for the C script
     * @param string $inputFile Path to file with input for the C script
     * @param string $expOutputFile Path to file with expected output returned by the C script
     * @param int $expExit Expected exit code returned by the C script
     * @param callable $callback What to call if the test was successful
     *
     * @throws ErrorInScriptException The test failed
     */
    public function testFileInput(string $name, string $script, string $params, string $inputFile, string $expOutputFile, int $expExit, callable $callback): void
    {
        $input = file_get_contents($inputFile);
        $expOutput = file_get_contents($expOutputFile);

        $this->testStringInput($name, $script, $params, $input, $expOutput, $expExit, $callback);
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
     * Getter for number of ran tests
     *
     * @return int Number of ran tests
     */
    public function getRan(): int
    {
        return $this->ran;
    }

    /**
     * Returns summary success rate
     *
     * @return int Summary success rate
     */
    public function getSuccessRate(): int
    {
        // Fix for division by zero error
        if ($this->ran === 0) {
            return 0;
        }

        return (int)round(($this->successful / $this->ran) * 100, 0);
    }

    /**
     * Returns summary fail rate
     *
     * @return int Summary fail rate
     */
    public function getFailRate(): int
    {
        // Fix for division by zero error
        if ($this->ran === 0) {
            return 0;
        }

        return (int)round(($this->failed / $this->ran) * 100, 0);
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