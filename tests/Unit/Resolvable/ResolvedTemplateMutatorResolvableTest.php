<?php

declare(strict_types=1);

namespace webignition\Stubble\Tests\Unit\Resolvable;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use webignition\Stubble\Resolvable\Resolvable;
use webignition\Stubble\Resolvable\ResolvableCollection;
use webignition\Stubble\Resolvable\ResolvableInterface;
use webignition\Stubble\Resolvable\ResolvedTemplateMutatorResolvable;

class ResolvedTemplateMutatorResolvableTest extends TestCase
{
    public function testGetTemplate(): void
    {
        $template = 'template content';
        $resolvable = new ResolvedTemplateMutatorResolvable(
            new Resolvable($template, []),
            function () {}
        );

        self::assertSame($template, $resolvable->getTemplate());
    }

    public function testGetContext(): void
    {
        $context = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $resolvable = new ResolvedTemplateMutatorResolvable(
            new Resolvable('', $context),
            function () {}
        );

        self::assertSame($context, $resolvable->getContext());
    }

    #[DataProvider('resolvedTemplateMutatorsDataProvider')]
    public function testResolvedTemplateMutators(
        string $resolvedTemplate,
        ResolvedTemplateMutatorResolvable $resolvable,
        string $expectedMutatedResolvedTemplate
    ): void {
        $mutators = $resolvable->getResolvedTemplateMutators();
        foreach ($mutators as $mutator) {
            $resolvedTemplate = $mutator($resolvedTemplate);
        }

        self::assertSame($expectedMutatedResolvedTemplate, $resolvedTemplate);
    }

    /**
     * @return array<mixed>
     */
    public static function resolvedTemplateMutatorsDataProvider(): array
    {
        return [
            'non-mutating inner resolvable' => [
                'resolvedTemplate' => 'content',
                'resolvable' => new ResolvedTemplateMutatorResolvable(
                    new Resolvable('content', []),
                    function (string $resolvedTemplate) {
                        return $resolvedTemplate . ' append 1';
                    }
                ),
                'expectedMutatedResolvedTemplate' => 'content append 1',
            ],
            'mutating inner resolvable' => [
                'resolvedTemplate' => 'content',
                'resolvable' => new ResolvedTemplateMutatorResolvable(
                    new ResolvedTemplateMutatorResolvable(
                        new Resolvable('content', []),
                        function (string $resolvedTemplate) {
                            return $resolvedTemplate . ' append 1';
                        }
                    ),
                    function (string $resolvedTemplate) {
                        return $resolvedTemplate . ' append 2';
                    }
                ),
                'expectedMutatedResolvedTemplate' => 'content append 1 append 2',
            ],
            'mutating inner resolvable inside mutating inner resolvable' => [
                'resolvedTemplate' => 'content',
                'resolvable' => new ResolvedTemplateMutatorResolvable(
                    new ResolvedTemplateMutatorResolvable(
                        new ResolvedTemplateMutatorResolvable(
                            new Resolvable('content', []),
                            function (string $resolvedTemplate) {
                                return $resolvedTemplate . ' append 1';
                            }
                        ),
                        function (string $resolvedTemplate) {
                            return $resolvedTemplate . ' append 2';
                        }
                    ),
                    function (string $resolvedTemplate) {
                        return $resolvedTemplate . ' append 3';
                    }
                ),
                'expectedMutatedResolvedTemplate' => 'content append 1 append 2 append 3',
            ],
        ];
    }

    public function testGetResolvable(): void
    {
        $encapsulatedResolvable = new Resolvable('', []);

        $resolvable = new ResolvedTemplateMutatorResolvable(
            $encapsulatedResolvable,
            function () {}
        );

        self::assertSame($encapsulatedResolvable, $resolvable->getResolvable());
    }

    #[DataProvider('countDataProvider')]
    public function testCount(ResolvedTemplateMutatorResolvable $resolvable, int $expectedCount): void
    {
        self::assertCount($expectedCount, $resolvable);
    }

    /**
     * @return array<mixed>
     */
    public static function countDataProvider(): array
    {
        return [
            'non-iterable inner resolvable' => [
                'resolvable' => new ResolvedTemplateMutatorResolvable(
                    new Resolvable('', []),
                    function () {},
                ),
                'expectedCount' => 1,
            ],
            'single-item inner resolvable' => [
                'resolvable' => new ResolvedTemplateMutatorResolvable(
                    new ResolvableCollection(['item1'], ''),
                    function () {},
                ),
                'expectedCount' => 1,
            ],
            'triple-item inner resolvable' => [
                'resolvable' => new ResolvedTemplateMutatorResolvable(
                    new ResolvableCollection(['item1', 'item2', 'item3'], ''),
                    function () {},
                ),
                'expectedCount' => 3,
            ],
        ];
    }

    #[DataProvider('getItemForIndexDataProvider')]
    public function testGetIndexForItem(
        ResolvedTemplateMutatorResolvable $collection,
        ResolvableInterface|string|\Stringable $item,
        ?int $expectedIndex
    ): void {
        self::assertSame($expectedIndex, $collection->getIndexForItem($item));
    }

    /**
     * @return array<mixed>
     */
    public static function getItemForIndexDataProvider(): array
    {
        $resolvable = new Resolvable('', []);
        $collection = new ResolvableCollection(
            [
                'item1',
                'item2',
                $resolvable,
            ],
            ''
        );
        $encapsulatedCollection = new ResolvedTemplateMutatorResolvable(
            $collection,
            function () {}
        );

        return [
            'non-iterable inner resolvable' => [
                'collection' => new ResolvedTemplateMutatorResolvable(
                    new Resolvable('', []),
                    function () {},
                ),
                'item' => 'item',
                'expectedIndex' => null,
            ],
            'empty collection' => [
                'collection' => new ResolvedTemplateMutatorResolvable(
                    new ResolvableCollection([], ''),
                    function () {}
                ),
                'item' => 'item',
                'expectedIndex' => null,
            ],
            'item not present' => [
                'collection' => $encapsulatedCollection,
                'item' => 'item',
                'expectedIndex' => null,
            ],
            'first item' => [
                'collection' => $encapsulatedCollection,
                'item' => 'item1',
                'expectedIndex' => 0,
            ],
            'last item' => [
                'collection' => $encapsulatedCollection,
                'item' => $resolvable,
                'expectedIndex' => 2,
            ],
        ];
    }
}
