<?php

declare(strict_types=1);

namespace webignition\Stubble\Tests\Unit;

use PHPUnit\Framework\TestCase;
use webignition\Stubble\UnresolvedVariableException;
use webignition\Stubble\VariableResolver;

class VariableResolverTest extends TestCase
{
    private VariableResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new VariableResolver();
    }

    /**
     * @dataProvider resolveDataProvider
     *
     * @param string $template
     * @param array<string, string> $context
     * @param string $expectedResolvedTemplate
     * @param callable[] $unresolvedVariableDeciders
     */
    public function testResolve(
        string $template,
        array $context,
        string $expectedResolvedTemplate,
        array $unresolvedVariableDeciders = []
    ) {
        foreach ($unresolvedVariableDeciders as $decider) {
            $this->resolver->addUnresolvedVariableDecider($decider);
        }

        $resolvedContent = $this->resolver->resolve($template, $context);

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
        $resolvedContent = $this->resolver->resolveAndIgnoreUnresolvedVariables($template, $context);

        self::assertSame($expectedResolvedTemplate, $resolvedContent);
    }

    /**
     * @dataProvider resolveThrowsUnresolvedVariableExceptionDataProvider
     *
     * @param string $template
     * @param array<string, string> $context
     * @param string $expectedVariable
     * @param callable[] $unresolvedVariableDeciders
     */
    public function testResolveThrowsUnresolvedVariableException(
        string $template,
        array $context,
        string $expectedVariable,
        array $unresolvedVariableDeciders = []
    ) {
        foreach ($unresolvedVariableDeciders as $decider) {
            $this->resolver->addUnresolvedVariableDecider($decider);
        }

        try {
            $this->resolver->resolve($template, $context);
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
                'unresolvedVariableDeciders' => [
                    function (string $variable) {
                        return 'variable1' === $variable;
                    },
                ],
            ],
        ];
    }

    /**
     * @dataProvider resolveDataProvider
     *
     * @param string $template
     * @param array<string, string> $context
     * @param string $expectedResolvedTemplate
     * @param callable[] $unresolvedVariableDeciders
     */
    public function testResolveTemplate(
        string $template,
        array $context,
        string $expectedResolvedTemplate,
        array $unresolvedVariableDeciders = []
    ) {
        self::assertSame(
            $expectedResolvedTemplate,
            VariableResolver::resolveTemplate($template, $context, $unresolvedVariableDeciders)
        );
    }

    /**
     * @dataProvider resolveThrowsUnresolvedVariableExceptionDataProvider
     *
     * @param string $template
     * @param array<string, string> $context
     * @param string $expectedVariable
     * @param callable[] $unresolvedVariableDeciders
     */
    public function testResolveTemplateThrowsUnresolvedVariableException(
        string $template,
        array $context,
        string $expectedVariable,
        array $unresolvedVariableDeciders = []
    ) {
        try {
            VariableResolver::resolveTemplate($template, $context, $unresolvedVariableDeciders);
        } catch (UnresolvedVariableException $unresolvedVariableException) {
            $this->assertSame($expectedVariable, $unresolvedVariableException->getVariable());
            $this->assertSame($template, $unresolvedVariableException->getTemplate());
        }
    }

    /**
     * @dataProvider resolveDataProvider
     *
     * @param string $template
     * @param array<string, string> $context
     * @param string $expectedResolvedTemplate
     */
    public function testResolveTemplateAndIgnoreUnresolvedVariables(
        string $template,
        array $context,
        string $expectedResolvedTemplate
    ) {
        self::assertSame(
            $expectedResolvedTemplate,
            VariableResolver::resolveTemplateAndIgnoreUnresolvedVariables($template, $context)
        );
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
                'unresolvedVariableDeciders' => [
                    function (string $variable) {
                        return 'name' === $variable || 'place' === $variable;
                    },
                ],
            ],
            'non-empty template, has missing variables allowed by different deciders' => [
                'template' => 'Hello {{ name }}, welcome to {{ place }}.',
                'context' => [],
                'expectedResolvedTemplate' => 'Hello {{ name }}, welcome to {{ place }}.',
                'unresolvedVariableDeciders' => [
                    function (string $variable) {
                        return 'name' === $variable || 'place' === $variable;
                    },
                    function (string $variable) {
                        return 'place' === $variable;
                    },
                ],
            ],
        ];
    }
}
