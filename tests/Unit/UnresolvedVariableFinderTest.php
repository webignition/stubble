<?php

declare(strict_types=1);

namespace webignition\Stubble\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use webignition\Stubble\UnresolvedVariableFinder;

class UnresolvedVariableFinderTest extends TestCase
{
    #[DataProvider('findFirstDataProvider')]
    public function testFindFirst(
        UnresolvedVariableFinder $finder,
        string $resolvedTemplate,
        ?string $expectedFirstUnresolvedVariable
    ): void {
        self::assertSame($expectedFirstUnresolvedVariable, $finder->findFirst($resolvedTemplate));
    }

    /**
     * @return array<mixed>
     */
    public static function findFirstDataProvider(): array
    {
        $defaultFinder = new UnresolvedVariableFinder();

        return [
            'empty template' => [
                'finder' => $defaultFinder,
                'resolvedTemplate' => '',
                'expectedFirstUnresolvedVariable' => null,
            ],
            'no unresolved variables' => [
                'finder' => $defaultFinder,
                'resolvedTemplate' => 'No unresolved variables',
                'expectedFirstUnresolvedVariable' => null,
            ],
            'single unresolved variable, no deciders' => [
                'finder' => $defaultFinder,
                'resolvedTemplate' => 'Hello Jon, welcome to {{ place }}.',
                'expectedFirstUnresolvedVariable' => 'place',
            ],
            'two unresolved variables, no deciders' => [
                'finder' => $defaultFinder,
                'resolvedTemplate' => 'Hello {{ name }}, welcome to {{ place }}.',
                'expectedFirstUnresolvedVariable' => 'name',
            ],
            'two unresolved variables, first allowed' => [
                'finder' => new UnresolvedVariableFinder([
                    function (string $variable) {
                        return 'name' === $variable;
                    },
                ]),
                'resolvedTemplate' => 'Hello {{ name }}, welcome to {{ place }}.',
                'expectedFirstUnresolvedVariable' => 'place',
            ],
            'two unresolved variables, both allowed' => [
                'finder' => new UnresolvedVariableFinder([
                    function (string $variable) {
                        return 'name' === $variable;
                    },
                    function (string $variable) {
                        return 'place' === $variable;
                    },
                ]),
                'resolvedTemplate' => 'Hello {{ name }}, welcome to {{ place }}.',
                'expectedFirstUnresolvedVariable' => null,
            ],
        ];
    }
}
