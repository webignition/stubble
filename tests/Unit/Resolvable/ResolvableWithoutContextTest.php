<?php

declare(strict_types=1);

namespace webignition\Stubble\Tests\Unit\Resolvable;

use PHPUnit\Framework\TestCase;
use webignition\Stubble\Resolvable\ResolvableWithoutContext;

class ResolvableWithoutContextTest extends TestCase
{
    public function testGetTemplate(): void
    {
        $content = 'pre-resolved content';

        $resolvable = new ResolvableWithoutContext($content);
        self::assertSame($content, $resolvable->getTemplate());
    }

    public function testGetContext(): void
    {
        $resolvable = new ResolvableWithoutContext('');
        self::assertSame([], $resolvable->getContext());
    }
}
