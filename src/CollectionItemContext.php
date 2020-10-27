<?php

declare(strict_types=1);

namespace webignition\Stubble;

class CollectionItemContext
{
    private int $index;
    private int $count;

    public function __construct(int $index, int $count)
    {
        $this->index = $index;
        $this->count = $count;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function isFirst(): bool
    {
        return 0 === $this->index;
    }

    public function isLast(): bool
    {
        return $this->index === $this->count - 1;
    }
}
