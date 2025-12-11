<?php

declare(strict_types=1);

namespace webignition\Stubble\Tests\Model;

class Stringable
{
    private string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function __toString(): string
    {
        return $this->content;
    }
}
