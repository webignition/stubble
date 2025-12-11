<?php

declare(strict_types=1);

namespace webignition\Stubble\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use webignition\Stubble\CollectionItemContext;
use webignition\Stubble\UnresolvedVariableException;
use webignition\Stubble\UnresolvedVariableFinder;
use webignition\Stubble\VariableResolver;
use webignition\StubbleResolvable\Resolvable;
use webignition\StubbleResolvable\ResolvableCollection;
use webignition\StubbleResolvable\ResolvableInterface;
use webignition\StubbleResolvable\ResolvedTemplateMutatorResolvable;

class VariableResolverTest extends TestCase
{
    #[DataProvider('resolveDataProvider')]
    public function testResolve(
        ResolvableInterface $resolvable,
        string $expectedResolvedTemplate,
        ?UnresolvedVariableFinder $unresolvedVariableFinder = null
    ): void {
        $resolver = new VariableResolver($unresolvedVariableFinder);

        $resolvedContent = $resolver->resolve($resolvable);

        self::assertSame($expectedResolvedTemplate, $resolvedContent);
    }

    #[DataProvider('resolveDataProvider')]
    public function testResolveAndIgnoreUnresolvedVariables(
        ResolvableInterface $resolvable,
        string $expectedResolvedTemplate,
        ?UnresolvedVariableFinder $unresolvedVariableFinder = null
    ): void {
        $resolver = new VariableResolver();

        $resolvedContent = $resolver->resolveAndIgnoreUnresolvedVariables($resolvable);

        self::assertSame($expectedResolvedTemplate, $resolvedContent);
    }

    #[DataProvider('resolveThrowsUnresolvedVariableExceptionDataProvider')]
    public function testResolveThrowsUnresolvedVariableException(
        ResolvableInterface $resolvable,
        string $expectedVariable,
        ?UnresolvedVariableFinder $unresolvedVariableFinder = null
    ): void {
        $resolver = new VariableResolver($unresolvedVariableFinder);

        try {
            $resolver->resolve($resolvable);
        } catch (UnresolvedVariableException $unresolvedVariableException) {
            $this->assertSame($expectedVariable, $unresolvedVariableException->getVariable());
            $this->assertSame($resolvable->getTemplate(), $unresolvedVariableException->getTemplate());
        }
    }

    /**
     * @return array<mixed>
     */
    public static function resolveThrowsUnresolvedVariableExceptionDataProvider(): array
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

    /**
     * @return array<mixed>
     */
    public static function resolveDataProvider(): array
    {
        $appendNewLineToAllButLastItemMutator = function (string $resolved, ?CollectionItemContext $context) {
            $appendNewLine = $context instanceof CollectionItemContext && false === $context->isLast();

            if ($appendNewLine) {
                $resolved .= "\n";
            }

            return $resolved;
        };

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
            'resolve resolvable context values' => [
                'resolvable' => new Resolvable('{{ content }}', [
                    'content' => new Resolvable(
                        '{{ key1 }} {{ key2 }}',
                        [
                            'key1' => 'value1',
                            'key2' => 'value2',
                        ]
                    ),
                ]),
                'expectedResolvedTemplate' => 'value1 value2',
            ],
            'resolve single resolvable with resolved template mutator' => [
                'resolvable' => new ResolvedTemplateMutatorResolvable(
                    new Resolvable(
                        '{{ key }}',
                        [
                            'key' => 'value',
                        ]
                    ),
                    function (string $resolved) {
                        return $resolved . '!';
                    }
                ),
                'expectedResolvedTemplate' => 'value!',
            ],
            'resolve collection of strings' => [
                'resolvable' => ResolvableCollection::create([
                    'item3',
                    'item1',
                    'item2',
                ]),
                'expectedResolvedTemplate' => 'item3item1item2',
            ],
            'resolve collection of strings, collection mutator' => [
                'resolvable' => new ResolvedTemplateMutatorResolvable(
                    ResolvableCollection::create([
                        'item3' . "\n",
                        'item1' . "\n",
                        'item2' . "\n",
                    ]),
                    function (string $resolvedCollection) {
                        $lines = explode("\n", $resolvedCollection);

                        $lines = array_map(function (string $line) {
                            return '' === $line ? '' : $line . '!';
                        }, $lines);

                        sort($lines);

                        return trim(implode("\n", $lines));
                    }
                ),
                'expectedResolvedTemplate' => 'item1!' . "\n" . 'item2!' . "\n" . 'item3!',
            ],
            'resolve collection of resolvable' => [
                'resolvable' => ResolvableCollection::create([
                    new Resolvable('Hello {{ first_name }} {{ last_name }}.', [
                        'first_name' => 'User',
                        'last_name' => 'Name',
                    ]),
                    new Resolvable('Proceed to room {{ room_number }} to learn {{ subject }}.', [
                        'room_number' => '101',
                        'subject' => 'French'
                    ]),
                ]),
                'expectedResolvedTemplate' => 'Hello User Name.Proceed to room 101 to learn French.',
            ],
            'resolve collection of resolvable, item mutators' => [
                'resolvable' => ResolvableCollection::create([
                    new ResolvedTemplateMutatorResolvable(
                        new Resolvable('Hello {{ first_name }} {{ last_name }}.', [
                            'first_name' => 'User',
                            'last_name' => 'Name',
                        ]),
                        function (string $resolved) {
                            return $resolved . "\n";
                        }
                    ),
                    new Resolvable('Proceed to room {{ room_number }} to learn {{ subject }}.', [
                        'room_number' => '101',
                        'subject' => 'French'
                    ]),
                ]),
                'expectedResolvedTemplate' => 'Hello User Name.' . "\n" . 'Proceed to room 101 to learn French.',
            ],
            'resolve collection of resolvable, context-aware item mutators, collection mutator' => [
                'resolvable' => new ResolvedTemplateMutatorResolvable(
                    ResolvableCollection::create([
                        new ResolvedTemplateMutatorResolvable(
                            new Resolvable('{{ name }}', [
                                'name' => 'item1',
                            ]),
                            $appendNewLineToAllButLastItemMutator
                        ),
                        new ResolvedTemplateMutatorResolvable(
                            new Resolvable('{{ name }}', [
                                'name' => 'item2',
                            ]),
                            $appendNewLineToAllButLastItemMutator
                        ),
                        new ResolvedTemplateMutatorResolvable(
                            new Resolvable('{{ name }}', [
                                'name' => 'item3',
                            ]),
                            $appendNewLineToAllButLastItemMutator
                        ),
                    ]),
                    function (string $resolved): string {
                        return $resolved . '!';
                    }
                ),
                'expectedResolvedTemplate' => 'item1' . "\n" .
                    'item2' . "\n" .
                    'item3!',
            ],
        ];
    }
}
