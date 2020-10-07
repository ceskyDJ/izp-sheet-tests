<?php

declare(strict_types=1);

/**
 * Exception for failed tests
 *
 * @author Michal ŠMAHEL <admin@ceskydj.cz>
 * @date October 2020
 */
class ErrorInScriptException extends Exception
{
    /**
     * The C script returned bad output (different from expected)
     */
    public const TYPE_BAD_OUTPUT = 0;
    /**
     * The C script ended with bad error code (different from expected)
     */
    public const TYPE_BAD_EXIT_CODE = 1;

    /**
     * @var int Test number (running position)
     */
    private int $number;
    /**
     * @var string Test name
     */
    private string $test;
    /**
     * @var int Test type (one of the public constants)
     */
    private int $type;

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int $number Test running position
     * @param string $test Test name
     * @param int $type Test type (select one of the public constants)
     * @param Throwable|null $previous [optional] Previous exception
     */
    public function __construct(string $message, int $number, string $test, int $type, ?Throwable $previous = null) {
        parent::__construct($message, 0, $previous);

        $this->number = $number;
        $this->test = $test;
        $this->type = $type;
    }

    /**
     * Getter for test number
     *
     * @return int Test number (running position)
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * Getter for test name
     *
     * @return string Test name
     */
    public function getTest(): string
    {
        return $this->test;
    }

    /**
     * Getter for test type
     *
     * @return int Test type
     */
    public function getType(): int
    {
        return $this->type;
    }
}