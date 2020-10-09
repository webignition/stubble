<?php

declare(strict_types=1);

namespace webignition\Stubble\Tests\Unit;

use PHPUnit\Framework\TestCase;
use webignition\Stubble\DeciderFactory;

class DeciderFactoryTest extends TestCase
{
    public function testCreateDisallowAllDecider()
    {
        $decider = DeciderFactory::createDisallowAllDecider();

        self::assertFalse($decider());
    }

    public function testCreateAllowAllDecider()
    {
        $decider = DeciderFactory::createAllowAllDecider();

        self::assertTrue($decider());
    }

    /**
     * @dataProvider createAllowByPatternDeciderDataProvider
     */
    public function testCreateAllowByPatternDecider(string $pattern, string $variable, bool $expectedResult)
    {
        $decider = DeciderFactory::createAllowByPatternDecider($pattern);

        self::assertSame($expectedResult, $decider($variable));
    }

    public function createAllowByPatternDeciderDataProvider(): array
    {
        return [
            'pattern does not match' => [
                'pattern' => '/variable[0-9]/',
                'variable' => 'abc',
                'expectedResult' => false,
            ],
            'pattern does match' => [
                'pattern' => '/variable[0-9]/',
                'variable' => 'variable1',
                'expectedResult' => true,
            ],
        ];
    }
}
