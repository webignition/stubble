<?php

declare(strict_types=1);

namespace webignition\Stubble\Resolvable;

class ResolvableWithoutContext implements ResolvableInterface
{
    private string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function getTemplate(): string
    {
        return $this->content;
    }

    public function getContext(): array
    {
        return [];
    }
}
