<?php

declare(strict_types=1);

namespace webignition\Stubble;

class VariableResolver
{
    private UnresolvedVariableFinder $unresolvedVariableFinder;

    public function __construct(?UnresolvedVariableFinder $unresolvedVariableFinder = null)
    {
        $this->unresolvedVariableFinder = $unresolvedVariableFinder ?? new UnresolvedVariableFinder([
            DeciderFactory::createDisallowAllDecider()
        ]);
    }

    /**
     * @param ResolvableInterface $resolvable
     *
     * @return string
     *
     * @throws UnresolvedVariableException
     */
    public function resolve(ResolvableInterface $resolvable): string
    {
        $resolvedTemplate = $this->doResolve($resolvable);

        $unresolvedVariable = $this->unresolvedVariableFinder->findFirst($resolvedTemplate);
        if (is_string($unresolvedVariable)) {
            throw new UnresolvedVariableException($unresolvedVariable, trim($resolvable->getTemplate()));
        }

        return $resolvedTemplate;
    }

    public function resolveAndIgnoreUnresolvedVariables(ResolvableInterface $resolvable): string
    {
        return $this->doResolve($resolvable);
    }

    private function doResolve(ResolvableInterface $resolvable): string
    {
        $search = [];
        $replace = [];

        foreach ($resolvable->getContext() as $key => $value) {
            $search[] = sprintf('/{{ ?%s ?}}/', $key);
            $replace[] = $value;
        }

        return (string) preg_replace($search, $replace, $resolvable->getTemplate());
    }
}
