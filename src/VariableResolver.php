<?php

declare(strict_types=1);

namespace webignition\Stubble;

class VariableResolver
{
    /**
     * @var callable[]
     */
    private array $unresolvedVariableDeciders = [];

    public function __construct()
    {
        $this->unresolvedVariableDeciders[] = function () {
            return false;
        };
    }

    /**
     * @param string $template
     * @param array<string, string> $context
     *
     * @return string
     *
     * @throws UnresolvedVariableException
     */
    public function resolve(string $template, array $context): string
    {
        $search = [];
        $replace = [];

        foreach ($context as $key => $value) {
            $search[] = sprintf('/{{ ?%s ?}}/', $key);
            $replace[] = $value;
        }

        $resolvedTemplate = (string) preg_replace($search, $replace, $template);

        $unresolvedVariableMatches = [];
        if (preg_match_all('/{{ ?[^{]+ ?}}/', $resolvedTemplate, $unresolvedVariableMatches)) {
            foreach ($unresolvedVariableMatches[0] as $unresolvedVariableMatch) {
                $unresolvedVariable = trim($unresolvedVariableMatch, '{} ');

                if (false === $this->isAllowedUnresolvedVariable($unresolvedVariable)) {
                    throw new UnresolvedVariableException($unresolvedVariable, trim($template));
                }
            }
        }

        return $resolvedTemplate;
    }

    public function addUnresolvedVariableDecider(callable $decider): void
    {
        $this->unresolvedVariableDeciders[] = $decider;
    }

    private function isAllowedUnresolvedVariable(string $variable): bool
    {
        $deciders = $this->unresolvedVariableDeciders;
        $deciders = array_reverse($deciders);

        foreach ($deciders as $decider) {
            if (true === $decider($variable)) {
                return true;
            }
        }

        return false;
    }
}
