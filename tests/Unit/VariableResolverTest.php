<?php

declare(strict_types=1);

namespace webignition\Stubble\Tests\Unit;

use PHPUnit\Framework\TestCase;
use webignition\Stubble\UnresolvedVariableException;
use webignition\Stubble\UnresolvedVariableFinder;
use webignition\Stubble\VariableResolver;
use webignition\StubbleResolvable\Resolvable;
use webignition\StubbleResolvable\ResolvableInterface;

class VariableResolverTest extends TestCase
{
    /**
     * @dataProvider resolveDataProvider
     */
    public function testResolve(
        ResolvableInterface $resolvable,
        string $expectedResolvedTemplate,
        ?UnresolvedVariableFinder $unresolvedVariableFinder = null
    ) {
        $resolver = new VariableResolver($unresolvedVariableFinder);

        $resolvedContent = $resolver->resolve($resolvable);

        self::assertSame($expectedResolvedTemplate, $resolvedContent);
    }

    /**
     * @dataProvider resolveDataProvider
     */
    public function testResolveAndIgnoreUnresolvedVariables(
        ResolvableInterface $resolvable,
        string $expectedResolvedTemplate
    ) {
        $resolver = new VariableResolver();

        $resolvedContent = $resolver->resolveAndIgnoreUnresolvedVariables($resolvable);

        self::assertSame($expectedResolvedTemplate, $resolvedContent);
    }

    /**
     * @dataProvider resolveThrowsUnresolvedVariableExceptionDataProvider
     */
    public function testResolveThrowsUnresolvedVariableException(
        ResolvableInterface $resolvable,
        string $expectedVariable,
        ?UnresolvedVariableFinder $unresolvedVariableFinder = null
    ) {
        $resolver = new VariableResolver($unresolvedVariableFinder);

        try {
            $resolver->resolve($resolvable);
        } catch (UnresolvedVariableException $unresolvedVariableException) {
            $this->assertSame($expectedVariable, $unresolvedVariableException->getVariable());
            $this->assertSame($resolvable->getTemplate(), $unresolvedVariableException->getTemplate());
        }
    }

    public function resolveThrowsUnresolvedVariableExceptionDataProvider(): array
    {
        return [
            'single variable' => [
                'resolvable' => new Resolvable('Content with {{variable}}', []),
                'expectedVariable' => 'variable',
            ],
            'two variables, both missing' => [
                'resolvable' => new Resolvable('Content with {{variable1}} and {{variable2}}', []),
                'expectedVariable' => 'variable1',
            ],
            'two variables, first missing' => [
                'resolvable' => new Resolvable(
                    'Content with {{variable1}} and {{variable2}}',
                    [
                        'variable2' => 'bar',
                    ]
                ),
                'expectedVariable' => 'variable1',
            ],
            'two variables, second missing' => [
                'resolvable' => new Resolvable(
                    'Content with {{variable1}} and {{variable2}}',
                    [
                        'variable1' => 'foo',
                    ]
                ),
                'expectedVariable' => 'variable2',
            ],
            'two variables, both missing, first allowed to be missing' => [
                'resolvable' => new Resolvable('Content with {{variable1}} and {{variable2}}', []),
                'expectedVariable' => 'variable2',
                'unresolvedVariableFinder' => new UnresolvedVariableFinder([
                    function (string $variable) {
                        return 'variable1' === $variable;
                    },
                ]),
            ],
        ];
    }

    public function resolveDataProvider(): array
    {
        return [
            'empty template, no variables' => [
                'resolvable' => new Resolvable('', []),
                'expectedResolvedTemplate' => '',
            ],
            'non-empty template, no variables' => [
                'resolvable' => new Resolvable('non-empty content', []),
                'expectedResolvedTemplate' => 'non-empty content',
            ],
            'non-empty template, has variables' => [
                'resolvable' => new Resolvable(
                    'Hello {{ name }}, welcome to {{ place }}.',
                    [
                        'name' => 'Jon',
                        'place' => 'Location',
                    ]
                ),
                'expectedResolvedTemplate' => 'Hello Jon, welcome to Location.',
            ],
            'non-empty template, has variables without surrounding whitespace' => [
                'resolvable' => new Resolvable(
                    'Hello {{name}}, welcome to {{place}}.',
                    [
                        'name' => 'Jon',
                        'place' => 'Location',
                    ]
                ),
                'expectedResolvedTemplate' => 'Hello Jon, welcome to Location.',
            ],
            'non-empty template, has missing variables allowed by same decider' => [
                'resolvable' => new Resolvable('Hello {{ name }}, welcome to {{ place }}.', []),
                'expectedResolvedTemplate' => 'Hello {{ name }}, welcome to {{ place }}.',
                'unresolvedVariableFinder' => new UnresolvedVariableFinder([
                    function (string $variable) {
                        return 'name' === $variable || 'place' === $variable;
                    },
                ]),
            ],
            'non-empty template, has missing variables allowed by different deciders' => [
                'resolvable' => new Resolvable('Hello {{ name }}, welcome to {{ place }}.', []),
                'expectedResolvedTemplate' => 'Hello {{ name }}, welcome to {{ place }}.',
                'unresolvedVariableFinder' => new UnresolvedVariableFinder([
                    function (string $variable) {
                        return 'name' === $variable || 'place' === $variable;
                    },
                    function (string $variable) {
                        return 'place' === $variable;
                    },
                ]),
            ],
            'retain escaped double slashes' => [
                'resolvable' => new Resolvable('{{ content }}', [
                    'content' => '\\\\"string\\\\"',
                ]),
                'expectedResolvedTemplate' => '\\\\"string\\\\"',
            ],
        ];
    }
}
