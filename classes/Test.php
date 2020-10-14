<?php

/**
 * Entity for test
 *
 * It's used for storing test's data and basic operations.
 *
 * @author Michal ŠMAHEL <admin@ceskydj.cz>
 * @date October 2020
 * @noinspection PhpIllegalPsrClassPathInspection
 */
class Test
{
    /**
     * @var int Test's number (position in testing)
     */
    private int $number;
    /**
     * @var int Test's difficulty level
     */
    private int $level = 0;
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
    private array $expStdOut = [];
    /**
     * @var int Expected exit code returned by the C script
     */
    private int $expExit = 0;
    /**
     * @var bool Is this test required to be successful?
     */
    private bool $required = true;

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
     * Returns commands tested by this test
     *
     * Yes, command<b>s</b>. One test can try many commands with different parameters
     *
     * @return string Full tested commands (script + parameters)
     */
    public function getTestedCommands(): string
    {
        $output = "";

        if (empty($this->paramsGroup)) {
            return "{$this->script}";
        }

        foreach ($this->paramsGroup as $params) {
            $output .= "{$this->script} {$params}".PHP_EOL;
        }

        return $output;
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
     * Getter for test's level
     *
     * @return int Test's difficulty level
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * Setter for test's level
     *
     * @param int $level Test's difficulty level
     *
     * @return Test Test's instance
     */
    public function setLevel(int $level): Test
    {
        $this->level = $level;

        return $this;
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
        $this->script = strstr($script, "/") ? $script : "./{$script}";

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
        // Empty input doesn't have any \n, so it sets bad value to $this->stdIn (not array)
        if ($input === "") {
            $this->stdIn = [];

            return $this;
        }

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

    /**
     * Getter for required state
     *
     * @return bool Is this test required to be successful?
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Setter for required state
     *
     * @param bool $required Is this test required to be successful?
     *
     * @return Test Test's instance
     */
    public function setRequired(bool $required): Test
    {
        $this->required = $required;

        return $this;
    }
}