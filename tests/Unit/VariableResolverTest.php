<?php

declare(strict_types=1);

namespace webignition\Stubble\Tests\Unit;

use PHPUnit\Framework\TestCase;
use webignition\Stubble\UnresolvedVariableException;
use webignition\Stubble\UnresolvedVariableFinder;
use webignition\Stubble\VariableResolver;

class VariableResolverTest extends TestCase
{
    /**
     * @dataProvider resolveDataProvider
     *
     * @param string $template
     * @param array<string, string> $context
     * @param string $expectedResolvedTemplate
     * @param UnresolvedVariableFinder|null $unresolvedVariableFinder
     */
    public function testResolve(
        string $template,
        array $context,
        string $expectedResolvedTemplate,
        ?UnresolvedVariableFinder $unresolvedVariableFinder = null
    ) {
        $resolver = new VariableResolver($unresolvedVariableFinder);

        $resolvedContent = $resolver->resolve($template, $context);

        self::assertSame($expectedResolvedTemplate, $resolvedContent);
    }

    /**
     * @dataProvider resolveDataProvider
     *
     * @param string $template
     * @param array<string, string> $context
     * @param string $expectedResolvedTemplate
     */
    public function testResolveAndIgnoreUnresolvedVariables(
        string $template,
        array $context,
        string $expectedResolvedTemplate
    ) {
        $resolver = new VariableResolver();

        $resolvedContent = $resolver->resolveAndIgnoreUnresolvedVariables($template, $context);

        self::assertSame($expectedResolvedTemplate, $resolvedContent);
    }

    /**
     * @dataProvider resolveThrowsUnresolvedVariableExceptionDataProvider
     *
     * @param string $template
     * @param array<string, string> $context
     * @param string $expectedVariable
     * @param UnresolvedVariableFinder|null $unresolvedVariableFinder
     */
    public function testResolveThrowsUnresolvedVariableException(
        string $template,
        array $context,
        string $expectedVariable,
        ?UnresolvedVariableFinder $unresolvedVariableFinder = null
    ) {
        $resolver = new VariableResolver($unresolvedVariableFinder);

        try {
            $resolver->resolve($template, $context);
        } catch (UnresolvedVariableException $unresolvedVariableException) {
            $this->assertSame($expectedVariable, $unresolvedVariableException->getVariable());
            $this->assertSame($template, $unresolvedVariableException->getTemplate());
        }
    }

    public function resolveThrowsUnresolvedVariableExceptionDataProvider(): array
    {
        return [
            'single variable' => [
                'template' => 'Content with {{variable}}',
                'context' => [],
                'expectedVariable' => 'variable',
            ],
            'two variables, both missing' => [
                'template' => 'Content with {{variable1}} and {{variable2}}',
                'context' => [],
                'expectedVariable' => 'variable1',
            ],
            'two variables, first missing' => [
                'template' => 'Content with {{variable1}} and {{variable2}}',
                'context' => [
                    'variable2' => 'bar',
                ],
                'expectedVariable' => 'variable1',
            ],
            'two variables, second missing' => [
                'template' => 'Content with {{variable1}} and {{variable2}}',
                'context' => [
                    'variable1' => 'foo',
                ],
                'expectedVariable' => 'variable2',
            ],
            'two variables, both missing, first allowed to be missing' => [
                'template' => 'Content with {{variable1}} and {{variable2}}',
                'context' => [],
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
                'template' => '',
                'context' => [],
                'expectedResolvedTemplate' => '',
            ],
            'non-empty template, no variables' => [
                'template' => 'non-empty content',
                'context' => [],
                'expectedResolvedTemplate' => 'non-empty content',
            ],
            'non-empty template, has variables' => [
                'template' => 'Hello {{ name }}, welcome to {{ place }}.',
                'context' => [
                    'name' => 'Jon',
                    'place' => 'Location',
                ],
                'expectedResolvedTemplate' => 'Hello Jon, welcome to Location.',
            ],
            'non-empty template, has variables without surrounding whitespace' => [
                'template' => 'Hello {{name}}, welcome to {{place}}.',
                'context' => [
                    'name' => 'Jon',
                    'place' => 'Location',
                ],
                'expectedResolvedTemplate' => 'Hello Jon, welcome to Location.',
            ],
            'non-empty template, has missing variables allowed by same decider' => [
                'template' => 'Hello {{ name }}, welcome to {{ place }}.',
                'context' => [],
                'expectedResolvedTemplate' => 'Hello {{ name }}, welcome to {{ place }}.',
                'unresolvedVariableFinder' => new UnresolvedVariableFinder([
                    function (string $variable) {
                        return 'name' === $variable || 'place' === $variable;
                    },
                ]),
            ],
            'non-empty template, has missing variables allowed by different deciders' => [
                'template' => 'Hello {{ name }}, welcome to {{ place }}.',
                'context' => [],
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
        ];
    }
}
