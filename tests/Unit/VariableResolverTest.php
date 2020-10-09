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
     * @param callable|null $initializer
     */
    public function testResolve(
        string $template,
        array $context,
        string $expectedResolvedTemplate,
        ?callable $initializer = null
    ) {
        if (is_callable($initializer)) {
            $initializer($this->resolver);
        }

        $resolvedContent = $this->resolver->resolve($template, $context);

        self::assertSame($expectedResolvedTemplate, $resolvedContent);
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
                'initializer' => function (VariableResolver $resolver) {
                    $resolver->addUnresolvedVariableDecider(function (string $variable) {
                        return 'name' === $variable || 'place' === $variable;
                    });
                },
            ],
            'non-empty template, has missing variables allowed by different deciders' => [
                'template' => 'Hello {{ name }}, welcome to {{ place }}.',
                'context' => [],
                'expectedResolvedTemplate' => 'Hello {{ name }}, welcome to {{ place }}.',
                'initializer' => function (VariableResolver $resolver) {
                    $resolver->addUnresolvedVariableDecider(function (string $variable) {
                        return 'name' === $variable;
                    });

                    $resolver->addUnresolvedVariableDecider(function (string $variable) {
                        return 'place' === $variable;
                    });
                },
            ],
        ];
    }

    /**
     * @dataProvider resolveThrowsUnresolvedVariableExceptionDataProvider
     *
     * @param string $content
     * @param array<string, string> $context
     * @param string $expectedVariable
     * @param callable|null $initializer
     */
    public function testResolveThrowsUnresolvedVariableException(
        string $content,
        array $context,
        string $expectedVariable,
        ?callable $initializer = null
    ) {
        if (is_callable($initializer)) {
            $initializer($this->resolver);
        }

        try {
            $this->resolver->resolve($content, $context);
        } catch (UnresolvedVariableException $unresolvedVariableException) {
            $this->assertSame($expectedVariable, $unresolvedVariableException->getVariable());
            $this->assertSame($content, $unresolvedVariableException->getTemplate());
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
                'initializer' => function (VariableResolver $resolver) {
                    $resolver->addUnresolvedVariableDecider(function (string $variable) {
                        return 'variable1' === $variable;
                    });
                },
            ],
        ];
    }

    /**
     * @dataProvider resolveTemplateDataProvider
     *
     * @param string $template
     * @param array<string, string> $context
     * @param callable[] $unresolvedVariableDeciders
     * @param string $expectedResolvedTemplate
     */
    public function testResolveTemplate(
        string $template,
        array $context,
        array $unresolvedVariableDeciders,
        string $expectedResolvedTemplate
    ) {
        self::assertSame(
            $expectedResolvedTemplate,
            VariableResolver::resolveTemplate($template, $context, $unresolvedVariableDeciders)
        );
    }

    public function resolveTemplateDataProvider(): array
    {
        return [
            'empty template, no variables' => [
                'template' => '',
                'context' => [],
                'unresolvedVariableDeciders' => [],
                'expectedResolvedTemplate' => '',
            ],
            'non-empty template, no variables' => [
                'template' => 'non-empty content',
                'context' => [],
                'unresolvedVariableDeciders' => [],
                'expectedResolvedTemplate' => 'non-empty content',
            ],
            'non-empty template, has variables' => [
                'template' => 'Hello {{ name }}, welcome to {{ place }}.',
                'context' => [
                    'name' => 'Jon',
                    'place' => 'Location',
                ],
                'unresolvedVariableDeciders' => [],
                'expectedResolvedTemplate' => 'Hello Jon, welcome to Location.',
            ],
            'non-empty template, has variables without surrounding whitespace' => [
                'template' => 'Hello {{name}}, welcome to {{place}}.',
                'context' => [
                    'name' => 'Jon',
                    'place' => 'Location',
                ],
                'unresolvedVariableDeciders' => [],
                'expectedResolvedTemplate' => 'Hello Jon, welcome to Location.',
            ],
            'non-empty template, has missing variables allowed by same decider' => [
                'template' => 'Hello {{ name }}, welcome to {{ place }}.',
                'context' => [],
                'unresolvedVariableDeciders' => [
                    function (string $variable) {
                        return 'name' === $variable || 'place' === $variable;
                    },
                ],
                'expectedResolvedTemplate' => 'Hello {{ name }}, welcome to {{ place }}.',
            ],
            'non-empty template, has missing variables allowed by different deciders' => [
                'template' => 'Hello {{ name }}, welcome to {{ place }}.',
                'context' => [],
                'unresolvedVariableDeciders' => [
                    function (string $variable) {
                        return 'name' === $variable;
                    },
                    function (string $variable) {
                        return 'place' === $variable;
                    },
                ],
                'expectedResolvedTemplate' => 'Hello {{ name }}, welcome to {{ place }}.',
            ],
        ];
    }
}
