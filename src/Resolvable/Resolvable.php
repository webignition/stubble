<?php

declare(strict_types=1);

namespace webignition\Stubble\Resolvable;

class Resolvable implements ResolvableInterface
{
    private string $template;

    /**
     * @var array<string, ResolvableInterface|string|\Stringable>
     */
    private array $context;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(string $template, array $context)
    {
        $this->template = $template;

        $filteredContext = [];
        foreach ($context as $key => $value) {
            if (is_string($value) || $value instanceof \Stringable || $value instanceof ResolvableInterface) {
                $filteredContext[$key] = $value;
            }
        }

        $this->context = $filteredContext;
    }

    public static function canResolve(mixed $item): bool
    {
        if (is_string($item)) {
            return true;
        }

        if ($item instanceof \Stringable) {
            return true;
        }

        return $item instanceof ResolvableInterface;
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
