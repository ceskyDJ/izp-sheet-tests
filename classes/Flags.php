<?php

/**
 * Flags enum for testing arguments' values
 *
 * @author Michal ŠMAHEL <admin@ceskydj.cz>
 * @date October 2020
 * @noinspection PhpIllegalPsrClassPathInspection
 */
class Flags
{
    /**
     * Integer type
     */
    public const INT = 1;
    /**
     * Floating point type
     */
    public const FLOAT = 2;
    /**
     * String type
     */
    public const STRING = 4;
    /**
     * Unsigned flag (value >= 0)
     */
    public const UNSIGNED = 8;
    /**
     * Not zero flag (value != 0)
     */
    public const NOT_ZERO = 16;
    /**
     * Smaller number
     */
    public const SMALLER = 32;
    /**
     * Bigger number
     */
    public const BIGGER = 64;
    /**
     * Standard int form used in most cases
     */
    public const STD_INT = self::INT | self::UNSIGNED | self::NOT_ZERO;
}