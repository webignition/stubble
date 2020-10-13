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
     * @param string $template
     * @param array<string, string> $context
     * @param UnresolvedVariableFinder|null $unresolvedVariableFinder
     *
     * @return string
     *
     * @throws UnresolvedVariableException
     */
    public static function resolveTemplate(
        string $template,
        array $context,
        ?UnresolvedVariableFinder $unresolvedVariableFinder = null
    ): string {
        $resolver = new VariableResolver($unresolvedVariableFinder);

        return $resolver->resolve($template, $context);
    }

    /**
     * @param string $template
     * @param array<string, string> $context
     *
     * @return string
     */
    public static function resolveTemplateAndIgnoreUnresolvedVariables(string $template, array $context): string
    {
        $resolver = new VariableResolver(
            new UnresolvedVariableFinder([])
        );

        return $resolver->resolveAndIgnoreUnresolvedVariables($template, $context);
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
        $resolvedTemplate = $this->doResolve($template, $context);

        $unresolvedVariable = $this->unresolvedVariableFinder->findFirst($resolvedTemplate);
        if (is_string($unresolvedVariable)) {
            throw new UnresolvedVariableException($unresolvedVariable, trim($template));
        }

        return $resolvedTemplate;
    }

    /**
     * @param string $template
     * @param array<string, string> $context
     *
     * @return string
     */
    public function resolveAndIgnoreUnresolvedVariables(string $template, array $context): string
    {
        return $this->doResolve($template, $context);
    }

    /**
     * @param string $template
     * @param array<string, string> $context
     *
     * @return string
     */
    private function doResolve(string $template, array $context)
    {
        $search = [];
        $replace = [];

        foreach ($context as $key => $value) {
            $search[] = sprintf('/{{ ?%s ?}}/', $key);
            $replace[] = $value;
        }

        return (string) preg_replace($search, $replace, $template);
    }
}
