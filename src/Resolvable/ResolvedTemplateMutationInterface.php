<?php

declare(strict_types=1);

namespace webignition\Stubble\Resolvable;

interface ResolvedTemplateMutationInterface
{
    /**
     * @return callable[]
     */
    public function getResolvedTemplateMutators(): array;
}
