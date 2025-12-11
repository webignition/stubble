<?php

declare(strict_types=1);

namespace webignition\Stubble;

use webignition\Stubble\Resolvable\ResolvableCollectionInterface;
use webignition\Stubble\Resolvable\ResolvableInterface;
use webignition\Stubble\Resolvable\ResolvedTemplateMutationInterface;

class VariableResolver
{
    private UnresolvedVariableFinder $unresolvedVariableFinder;

    public function __construct(?UnresolvedVariableFinder $unresolvedVariableFinder = null)
    {
        $this->unresolvedVariableFinder = $unresolvedVariableFinder ?? new UnresolvedVariableFinder([
            DeciderFactory::createDisallowAllDecider(),
        ]);
    }

    /**
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
        return $this->doResolve($resolvable);
    }

    private function doResolve(ResolvableInterface $resolvable, ?CollectionItemContext $itemContext = null): string
    {
        $template = $resolvable->getTemplate();
        $context = $resolvable->getContext();

        $search = [];
        $replace = [];

        foreach ($context as $key => $value) {
            if ($value instanceof ResolvableInterface) {
                $value = $this->doResolve(
                    $value,
                    $this->createCollectionItemContext($resolvable, $value)
                );
            }

            $searchVariants = $this->createKeySearchVariants($key);
            $replacements = array_fill(0, count($searchVariants), $value);

            $search = $this->filterToStringValuesOnly(array_merge($search, $searchVariants));
            $replace = $this->filterToStringValuesOnly(array_merge($replace, $replacements));
        }

        $resolved = (string) str_replace($search, $replace, $template);

        if ($resolvable instanceof ResolvedTemplateMutationInterface) {
            $mutators = $resolvable->getResolvedTemplateMutators();
            foreach ($mutators as $mutator) {
                $resolved = $mutator($resolved, $itemContext);
            }
        }

        return $resolved;
    }

    /**
     * @param array<mixed> $data
     *
     * @return string[]
     */
    private function filterToStringValuesOnly(array $data): array
    {
        $filteredData = [];
        foreach ($data as $value) {
            if (is_string($value)) {
                $filteredData[] = $value;
            }
        }

        return $filteredData;
    }

    private function createCollectionItemContext(
        ResolvableInterface $resolvable,
        ResolvableInterface|string|\Stringable $item
    ): ?CollectionItemContext {
        if (!$resolvable instanceof ResolvableCollectionInterface) {
            return null;
        }

        $position = $resolvable->getIndexForItem($item);
        if (null === $position) {
            return null;
        }

        return new CollectionItemContext($position, count($resolvable));
    }

    /**
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
