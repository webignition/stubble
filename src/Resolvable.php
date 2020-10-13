<?php

declare(strict_types=1);

namespace webignition\Stubble;

class Resolvable implements ResolvableInterface
{
    private string $template;

    /**
     * @var array<string, string>
     */
    private array $context;

    /**
     * @param string $template
     * @param array<string, string> $context
     */
    public function __construct(string $template, array $context)
    {
        $this->template = $template;
        $this->context = $context;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
