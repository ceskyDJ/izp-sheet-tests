<?php

/**
 * Generator for automatic creating similar tests
 *
 * @author Michal ŠMAHEL <admin@ceskydj.cz>
 * @date October 2020
 * @noinspection PhpIllegalPsrClassPathInspection
 */
class TestGenerator
{
    /**
     * Custom error code returned by C script
     */
    private const ERROR_CODE = 1;
    /**
     * Some valid input for testing exit codes
     */
    private const SOME_INPUT = [
        ":::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::",
        ":::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::",
        ":::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::",
    ];
    /**
     * String with alphabet chars
     */
    private const ALPHABET = "abcdefghijklmnopqrstuvwxyz";

    /**
     * @var Tester Tester to create tests for
     */
    private Tester $tester;

    /**
     * TestGenerator constructor
     *
     * @param \Tester $tester Tester to create tests for
     */
    public function __construct(Tester $tester)
    {
        $this->tester = $tester;
    }

    /**
     * Generates set of tests with providing bad input parameters to specific function
     *
     * @param string $script Script for testing
     * @param string $function Script's function to test
     * @param array $argsFlags Arguments' flags (type and other flags)
     */
    public function generateBadInputParamsTests(
        string $script,
        string $function,
        array $argsFlags = []
    ): void {
        $this->generateBadNumberOfParamsTest($script, $function, count($argsFlags));

        if (!empty($argsFlags)) {
            $this->generateUnexpectedStringParamTest($script, $function, $argsFlags);
            $this->generateUnexpectedFloatParamTest($script, $function, $argsFlags);
            $this->generateZeroValueParametersTest($script, $function, $argsFlags);
            $this->generateNegativeValueParametersTest($script, $function, $argsFlags);
        }

        if (count($argsFlags) >= 2) {
            $this->generateReverseOrderNumbersInParamsTest($script, $function, $argsFlags);
        }

        if (count($argsFlags) >= 3) {
            $this->generateBadValueForOutOfIntervalParamTest($script, $function, $argsFlags);
        }
    }

    /**
     * Generates a bad input test for checking bad number of parameters
     *
     * @param string $script Script for testing
     * @param string $function Script's function to test
     * @param int $numberOfParams Real (true) number of parameters
     */
    private function generateBadNumberOfParamsTest(string $script, string $function, int $numberOfParams): void
    {
        $params = "";
        $fakeNumOfParams = rand($numberOfParams + 1, $numberOfParams + 5);
        for ($i = 0; $i < $fakeNumOfParams; $i++) {
            $params .= " ".rand(1, 20);
        }

        $this->tester->createTest()
            ->setName("{$function} with bad number of parameters")
            ->setScript($script)
            ->addParams("-d : {$function}{$params}")
            ->setStdIn(self::SOME_INPUT)
            ->setExpExitCode(self::ERROR_CODE);
    }

    /**
     * Generates a bad input test for checking unexpected string value in int and float params
     *
     * @param string $script Script for testing
     * @param string $function Script's function to test
     * @param array $argsFlags Arguments' flags (type and other flags)
     */
    private function generateUnexpectedStringParamTest(string $script, string $function, array $argsFlags): void
    {
        $params = "";
        $flagsForTesting = 0;
        foreach ($argsFlags as $individualArgFlags) {
            if ($individualArgFlags & Flags::INT || $individualArgFlags & Flags::FLOAT) {
                $flagsForTesting++;

                $params .= " {$this->generateRandomString()}";
            } else {
                $params .= " *";
            }
        }

        // The test cannot be created, because no flags have to be tested
        if ($flagsForTesting === 0) {
            return;
        }

        $this->tester->createTest()
            ->setName("{$function} with unexpected string value parameter(s)")
            ->setScript($script)
            ->addParams("-d : {$function}{$params}")
            ->setStdIn(self::SOME_INPUT)
            ->setExpExitCode(self::ERROR_CODE);
    }

    /**
     * Generates a bad input test for checking unexpected float value in int params
     *
     * @param string $script Script for testing
     * @param string $function Script's function to test
     * @param array $argsFlags Arguments' flags (type and other flags)
     */
    private function generateUnexpectedFloatParamTest(string $script, string $function, array $argsFlags): void
    {
        $params = "";
        $flagsForTesting = 0;
        foreach ($argsFlags as $individualArgFlags) {
            if ($individualArgFlags & Flags::INT) {
                $flagsForTesting++;

                $params .= " ".rand(1, 99) / 100;
            } else {
                $params .= " 1";
            }
        }

        // The test cannot be created, because no flags have to be tested
        if ($flagsForTesting === 0) {
            return;
        }

        $this->tester->createTest()
            ->setName("{$function} with unexpected floating point value parameter(s)")
            ->setScript($script)
            ->addParams("-d : {$function}{$params}")
            ->setStdIn(self::SOME_INPUT)
            ->setExpExitCode(self::ERROR_CODE);
    }

    /**
     * Generates a bad input test for checking zero value parameter(s)
     *
     * @param string $script Script for testing
     * @param string $function Script's function to test
     * @param array $argsFlags Arguments' flags (type and other flags)
     */
    private function generateZeroValueParametersTest(string $script, string $function, array $argsFlags): void
    {
        $flagsForTesting = 0;
        foreach ($argsFlags as $individualArgFlags) {
            if ($individualArgFlags & Flags::NOT_ZERO) {
                $flagsForTesting++;
            }
        }

        // The test cannot be created, because no flags have to be tested
        if ($flagsForTesting === 0) {
            return;
        }

        $params = str_repeat(" 0", count($argsFlags));

        $this->tester->createTest()
            ->setName("{$function} with zero value parameter(s)")
            ->setScript($script)
            ->addParams("-d : {$function}{$params}")
            ->setStdIn(self::SOME_INPUT)
            ->setExpExitCode(self::ERROR_CODE);
    }

    /**
     * Generates a bad input test for checking negative value parameter(s)
     *
     * @param string $script Script for testing
     * @param string $function Script's function to test
     * @param array $argsFlags Arguments' flags (type and other flags)
     */
    private function generateNegativeValueParametersTest(string $script, string $function, array $argsFlags): void
    {
        $flagsForTesting = 0;
        $params = "";
        foreach ($argsFlags as $individualArgFlags) {
            if ($individualArgFlags & Flags::UNSIGNED) {
                $flagsForTesting++;

                $params .= " ".rand(-20, -1);
            }
        }

        // The test cannot be created, because no flags have to be tested
        if ($flagsForTesting === 0) {
            return;
        }

        $this->tester->createTest()
            ->setName("{$function} with negative value parameter(s)")
            ->setScript($script)
            ->addParams("-d : {$function}{$params}")
            ->setStdIn(self::SOME_INPUT)
            ->setExpExitCode(self::ERROR_CODE);
    }

    /**
     * Generates a bad input test for checking reverse order numbers in parameter(s)
     *
     * @param string $script Script for testing
     * @param string $function Script's function to test
     * @param array $argsFlags Arguments' flags (type and other flags)
     */
    private function generateReverseOrderNumbersInParamsTest(string $script, string $function, array $argsFlags): void
    {
        $params = "";
        $smaller = null;
        $bigger = null;
        foreach ($argsFlags as $individualArgFlags) {
            if ($individualArgFlags & Flags::SMALLER) {
                // It's bigger because this method generates bad input test
                // The test is for checking how well the program handles this bad inputs
                $bigger = rand(40, 70);
                $params .= " {$bigger}";
            } elseif ($individualArgFlags & Flags::BIGGER) {
                // See $bigger above
                $smaller = rand(1, $bigger);
                $params .= " {$smaller}";
            } else {
                $params .= " ".rand(1, 50);
            }
        }

        // The test cannot be created, if there is no smaller or bigger number
        // In this case there is no rule for number order
        if ($smaller === null || $bigger === null) {
            return;
        }

        $this->tester->createTest()
            ->setName("{$function} with reverse order numbers in parameter(s)")
            ->setScript($script)
            ->addParams("-d : {$function}{$params}")
            ->setStdIn(self::SOME_INPUT)
            ->setExpExitCode(self::ERROR_CODE);
    }


    /**
     * Generates a bad input test for checking bad value in parameter that must be out of interval <SMALLER, BIGGER>
     *
     * @param string $script Script for testing
     * @param string $function Script's function to test
     * @param array $argsFlags Arguments' flags (type and other flags)
     */
    private function generateBadValueForOutOfIntervalParamTest(string $script, string $function, array $argsFlags): void
    {
        $params = "";
        // Param that should be out of interval <$smaller, $bigger>
        $out = null;
        $smaller = null;
        foreach ($argsFlags as $individualArgFlags) {
            if ($individualArgFlags & Flags::OUT) {
                $out = rand(25, 60);
            } else if ($individualArgFlags & Flags::SMALLER) {
                $smaller = rand($out - 25, 40);
                $params .= " {$smaller}";
            } elseif ($individualArgFlags & Flags::BIGGER) {
                $bigger = rand($smaller + 1, 70);
                $params .= " {$bigger}";
            } else {
                $params .= " ".rand(1, 50);
            }
        }

        // The test cannot be created, if there is no out of interval number
        // In this case there is no rule required for this test
        if ($out === null) {
            return;
        }

        $this->tester->createTest()
            ->setName("{$function} with bad value of parameter that must be out of interval <smaller, bigger>")
            ->setScript($script)
            ->addParams("-d : {$function}{$params}")
            ->setStdIn(self::SOME_INPUT)
            ->setExpExitCode(self::ERROR_CODE);
    }

    /**
     * Generates random string
     *
     * @return string Random string
     */
    private function generateRandomString(): string
    {
        $stringParam = str_shuffle(self::ALPHABET);
        $cutStart = rand(0, round(strlen($stringParam) / 2));
        $cutLength = rand($cutStart, strlen($stringParam) - $cutStart);

        return substr($stringParam, $cutStart, $cutLength);
    }
}