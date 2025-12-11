<?php

declare(strict_types=1);

namespace webignition\Stubble\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use webignition\Stubble\DeciderFactory;

class DeciderFactoryTest extends TestCase
{
    public function testCreateDisallowAllDecider(): void
    {
        $decider = DeciderFactory::createDisallowAllDecider();

        self::assertFalse($decider());
    }

    public function testCreateAllowAllDecider(): void
    {
        $decider = DeciderFactory::createAllowAllDecider();

        self::assertTrue($decider());
    }

    #[DataProvider('createAllowByPatternDeciderDataProvider')]
    public function testCreateAllowByPatternDecider(string $pattern, string $variable, bool $expectedResult): void
    {
        $decider = DeciderFactory::createAllowByPatternDecider($pattern);

        self::assertSame($expectedResult, $decider($variable));
    }

    /**
     * @return array<mixed>
     */
    public static function createAllowByPatternDeciderDataProvider(): array
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
