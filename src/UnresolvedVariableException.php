<?php

declare(strict_types=1);

namespace webignition\Stubble;

class UnresolvedVariableException extends \Exception
{
    private string $variable;
    private string $template;

    public function __construct(string $variable, string $template)
    {
        parent::__construct(sprintf('Unresolved variable "%s" in template "%s"', $variable, $template));

        $this->variable = $variable;
        $this->template = $template;
    }

    public function getVariable(): string
    {
        return $this->variable;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }
}
