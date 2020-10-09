<?php

declare(strict_types=1);

namespace webignition\Stubble;

class DeciderFactory
{
    public static function createDisallowAllDecider(): callable
    {
        return function () {
            return false;
        };
    }

    public static function createAllowAllDecider(): callable
    {
        return function () {
            return true;
        };
    }

    public static function createAllowByPatternDecider(string $pattern): callable
    {
        return function (string $variable) use ($pattern) {
            return preg_match($pattern, $variable) > 0;
        };
    }
}
