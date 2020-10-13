<?php

declare(strict_types=1);

namespace webignition\Stubble;

class UnresolvedVariableFinder
{
    /**
     * @var callable[]
     */
    private array $unresolvedVariableDeciders = [];

    public function __construct()
    {
        $this->unresolvedVariableDeciders[] = DeciderFactory::createDisallowAllDecider();
    }

    public function addDecider(callable $decider): void
    {
        $this->unresolvedVariableDeciders[] = $decider;
    }

    public function findFirst(string $resolvedTemplate): ?string
    {
        $unresolvedVariableMatches = [];
        if (preg_match_all('/{{ ?[^{]+ ?}}/', $resolvedTemplate, $unresolvedVariableMatches)) {
            foreach ($unresolvedVariableMatches[0] as $unresolvedVariableMatch) {
                $unresolvedVariable = trim($unresolvedVariableMatch, '{} ');

                if (false === $this->isAllowedUnresolvedVariable($unresolvedVariable)) {
                    return $unresolvedVariable;
                }
            }
        }

        return null;
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
