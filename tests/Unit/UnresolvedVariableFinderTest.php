<?php

declare(strict_types=1);

namespace webignition\Stubble\Tests\Unit;

use PHPUnit\Framework\TestCase;
use webignition\Stubble\UnresolvedVariableFinder;

class UnresolvedVariableFinderTest extends TestCase
{
    private UnresolvedVariableFinder $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = new UnresolvedVariableFinder();
    }

    /**
     * @dataProvider findFirstDataProvider
     *
     * @param string $resolvedTemplate
     * @param callable[] $deciders
     * @param string|null $expectedFirstUnresolvedVariable
     */
    public function testFindFirst(string $resolvedTemplate, array $deciders, ?string $expectedFirstUnresolvedVariable)
    {
        foreach ($deciders as $decider) {
            $this->finder->addDecider($decider);
        }

        self::assertSame($expectedFirstUnresolvedVariable, $this->finder->findFirst($resolvedTemplate));
    }

    public function findFirstDataProvider(): array
    {
        return [
            'empty template' => [
                'resolvedTemplate' => '',
                'deciders' => [],
                'expectedFirstUnresolvedVariable' => null,
            ],
            'no unresolved variables' => [
                'resolvedTemplate' => 'No unresolved variables',
                'deciders' => [],
                'expectedFirstUnresolvedVariable' => null,
            ],
            'single unresolved variable, no deciders' => [
                'resolvedTemplate' => 'Hello Jon, welcome to {{ place }}.',
                'deciders' => [],
                'expectedFirstUnresolvedVariable' => 'place',
            ],
            'two unresolved variables, no deciders' => [
                'resolvedTemplate' => 'Hello {{ name }}, welcome to {{ place }}.',
                'deciders' => [],
                'expectedFirstUnresolvedVariable' => 'name',
            ],
            'two unresolved variables, first allowed' => [
                'resolvedTemplate' => 'Hello {{ name }}, welcome to {{ place }}.',
                'deciders' => [
                    function (string $variable) {
                        return 'name' === $variable;
                    },
                ],
                'expectedFirstUnresolvedVariable' => 'place',
            ],
            'two unresolved variables, both allowed' => [
                'resolvedTemplate' => 'Hello {{ name }}, welcome to {{ place }}.',
                'deciders' => [
                    function (string $variable) {
                        return 'name' === $variable;
                    },
                    function (string $variable) {
                        return 'place' === $variable;
                    },
                ],
                'expectedFirstUnresolvedVariable' => null,
            ],
        ];
    }
}
