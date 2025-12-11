<?php

declare(strict_types=1);

namespace webignition\Stubble\Resolvable;

interface ResolvableCollectionInterface extends ResolvableInterface, \Countable
{
    public function getIndexForItem(ResolvableInterface|string|\Stringable $item): ?int;
}
