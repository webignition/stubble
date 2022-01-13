<?php

declare(strict_types=1);

namespace webignition\Stubble;

use webignition\StubbleResolvable\ResolvableCollectionInterface;
use webignition\StubbleResolvable\ResolvableInterface;
use webignition\StubbleResolvable\ResolvedTemplateMutationInterface;

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

            $search = array_merge($search, $searchVariants);
            $replace = array_merge($replace, $replacements);
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
     * @param ResolvableInterface|string $item
     */
    private function createCollectionItemContext(ResolvableInterface $resolvable, $item): ?CollectionItemContext
    {
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
