<?php

declare(strict_types=1);

namespace webignition\Stubble;

use webignition\StubbleResolvable\ResolvableInterface;

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
        $resolvedTemplate = $this->resolveAndIgnoreUnresolvedVariables($resolvable);

        $unresolvedVariable = $this->unresolvedVariableFinder->findFirst($resolvedTemplate);
        if (is_string($unresolvedVariable)) {
            throw new UnresolvedVariableException($unresolvedVariable, trim($resolvable->getTemplate()));
        }

        return $resolvedTemplate;
    }

    public function resolveAndIgnoreUnresolvedVariables(ResolvableInterface $resolvable): string
    {
        $template = $resolvable->getTemplate();
        $context = $resolvable->getContext();

        $search = [];
        $replace = [];

        foreach ($context as $key => $value) {
            if ($value instanceof ResolvableInterface) {
                $value = $this->resolveAndIgnoreUnresolvedVariables($value);
            }

            $searchVariants = $this->createKeySearchVariants($key);
            $replacements = array_fill(0, count($searchVariants), $value);

            $search = array_merge($search, $searchVariants);
            $replace = array_merge($replace, $replacements);
        }

        $resolved = (string) str_replace($search, $replace, $template);

        $mutator = $resolvable->getResolvedTemplateMutator();
        if (is_callable($mutator)) {
            $resolved = ($mutator)($resolved);
        }

        return $resolved;
    }

    /**
     * @param string $key
     *
     * @return string[]
     */
    private function createKeySearchVariants(string $key): array
    {
        return [
            '{{' . $key . '}}',
            '{{' . $key . ' }}',
            '{{ ' . $key . '}}',
            '{{ ' . $key . ' }}',
        ];
    }
}
