<?php

declare(strict_types=1);

namespace webignition\Stubble;

class VariableResolver
{
    private const THROW_UNRESOLVED_VARIABLE_EXCEPTIONS = true;
    private const IGNORE_UNRESOLVED_VARIABLE_EXCEPTIONS = false;

    /**
     * @var callable[]
     */
    private array $unresolvedVariableDeciders = [];

    public function __construct()
    {
        $this->unresolvedVariableDeciders[] = DeciderFactory::createDisallowAllDecider();
    }

    /**
     * @param string $template
     * @param array<string, string> $context
     * @param callable[] $deciders
     *
     * @return string
     *
     * @throws UnresolvedVariableException
     */
    public static function resolveTemplate(string $template, array $context, array $deciders = []): string
    {
        return self::createResolver($deciders)->resolve($template, $context);
    }

    /**
     * @param string $template
     * @param array<string, string> $context
     *
     * @return string
     *
     * @throws UnresolvedVariableException
     */
    public static function resolveTemplateAndIgnoreUnresolvedVariables(string $template, array $context): string
    {
        return self::createResolver([])->resolveAndIgnoreUnresolvedVariables($template, $context);
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
        return $this->doResolve($template, $context, self::THROW_UNRESOLVED_VARIABLE_EXCEPTIONS);
    }

    /**
     * @param string $template
     * @param array<string, string> $context
     *
     * @return string
     *
     * @throws UnresolvedVariableException
     */
    public function resolveAndIgnoreUnresolvedVariables(string $template, array $context): string
    {
        return $this->doResolve($template, $context, self::IGNORE_UNRESOLVED_VARIABLE_EXCEPTIONS);
    }

    public function addUnresolvedVariableDecider(callable $decider): void
    {
        $this->unresolvedVariableDeciders[] = $decider;
    }

    /**
     * @param string $template
     * @param array<string, string> $context
     * @param bool $throwExceptionOnUnresolvedVariable
     *
     * @return string
     *
     * @throws UnresolvedVariableException
     */
    private function doResolve(string $template, array $context, bool $throwExceptionOnUnresolvedVariable)
    {
        $search = [];
        $replace = [];

        foreach ($context as $key => $value) {
            $search[] = sprintf('/{{ ?%s ?}}/', $key);
            $replace[] = $value;
        }

        $resolvedTemplate = (string) preg_replace($search, $replace, $template);

        if (true === $throwExceptionOnUnresolvedVariable) {
            $unresolvedVariable = $this->findFirstUnresolvedVariable($resolvedTemplate);
            if (is_string($unresolvedVariable) && false === $this->isAllowedUnresolvedVariable($unresolvedVariable)) {
                throw new UnresolvedVariableException($unresolvedVariable, trim($template));
            }
        }

        return $resolvedTemplate;
    }

    private function findFirstUnresolvedVariable(string $resolvedTemplate): ?string
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

    /**
     * @param callable[] $unresolvedVariableDeciders
     *
     * @return self
     */
    private static function createResolver(array $unresolvedVariableDeciders): self
    {
        $resolver = new VariableResolver();
        foreach ($unresolvedVariableDeciders as $decider) {
            if (is_callable($decider)) {
                $resolver->addUnresolvedVariableDecider($decider);
            }
        }

        return $resolver;
    }
}
