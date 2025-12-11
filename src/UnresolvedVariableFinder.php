<?php

declare(strict_types=1);

namespace webignition\Stubble;

class UnresolvedVariableFinder
{
    /**
     * @var callable[]
     */
    private array $deciders;

    /**
     * @param callable[] $deciders
     */
    public function __construct(array $deciders = [])
    {
        $this->deciders = [
            DeciderFactory::createDisallowAllDecider(),
        ];

        $this->deciders = array_merge(
            [
                DeciderFactory::createDisallowAllDecider(),
            ],
            $deciders
        );
    }

    public function addDecider(callable $decider): void
    {
        $this->deciders[] = $decider;
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
        $deciders = $this->deciders;
        $deciders = array_reverse($deciders);

        foreach ($deciders as $decider) {
            if (true === $decider($variable)) {
                return true;
            }
        }

        return false;
    }
}
