<?php

declare(strict_types=1);

namespace webignition\Stubble\Resolvable;

interface ResolvableProviderInterface
{
    public function getResolvable(): ResolvableInterface;
}
