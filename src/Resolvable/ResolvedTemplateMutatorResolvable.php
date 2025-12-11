<?php

declare(strict_types=1);

namespace webignition\Stubble\Resolvable;

use webignition\Stubble\Resolvable\ResolvableCollectionInterface as CollectionInterface;
use webignition\Stubble\Resolvable\ResolvableProviderInterface as ProviderInterface;
use webignition\Stubble\Resolvable\ResolvedTemplateMutationInterface as TemplateMutationInterface;

class ResolvedTemplateMutatorResolvable implements CollectionInterface, ProviderInterface, TemplateMutationInterface
{
    private ResolvableInterface $resolvable;

    /**
     * @var callable
     */
    private $mutator;

    public function __construct(ResolvableInterface $resolvable, callable $mutator)
    {
        $this->resolvable = $resolvable;
        $this->mutator = $mutator;
    }

    public function getTemplate(): string
    {
        return $this->resolvable->getTemplate();
    }

    public function getContext(): array
    {
        return $this->resolvable->getContext();
    }

    /**
     * @return callable[]
     */
    public function getResolvedTemplateMutators(): array
    {
        $mutators = [];

        if ($this->resolvable instanceof TemplateMutationInterface) {
            $mutators = array_merge($mutators, $this->resolvable->getResolvedTemplateMutators());
        }

        $mutators[] = $this->mutator;

        return $mutators;
    }

    public function getResolvable(): ResolvableInterface
    {
        return $this->resolvable;
    }

    public function count(): int
    {
        if ($this->resolvable instanceof CollectionInterface) {
            return $this->resolvable->count();
        }

        return 1;
    }

    public function getIndexForItem(ResolvableInterface|string|\Stringable $item): ?int
    {
        if ($this->resolvable instanceof CollectionInterface) {
            return $this->resolvable->getIndexForItem($item);
        }

        return null;
    }
}
