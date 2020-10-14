<?php

declare(strict_types=1);

/**
 * Exception for failed tests
 *
 * @author Michal ŠMAHEL <admin@ceskydj.cz>
 * @date October 2020
 * @noinspection PhpIllegalPsrClassPathInspection
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
     * @var Test Test' instance
     */
    private Test $test;
    /**
     * @var int Test type (one of the public constants)
     */
    private int $type;

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param Test $test Test's instance
     * @param int $type Test type (select one of the public constants)
     * @param Throwable|null $previous [optional] Previous exception
     */
    public function __construct(string $message, Test $test, int $type, ?Throwable $previous = null) {
        parent::__construct($message, 0, $previous);

        $this->test = $test;
        $this->type = $type;
    }

    /**
     * Getter for test
     *
     * @return Test Test's instance
     */
    public function getTest(): Test
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