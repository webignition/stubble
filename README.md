# Stubble
A minimal library to replace mustache-like variables in strings, using PHP.

## Installing

`composer require webignition/stubble`


## Hello {{ name }}! Example

```php
use webignition\Stubble\VariableResolver;

// Using a VariableResolver instance
$resolver = new VariableResolver();

$template = 'Hello {{ name }}!';
$context = [
    'name' => 'World',
];

$resolvedTemplate = $resolver->resolve($template, $context);
echo $resolvedTemplate; // Hello World!

// Or calling statically
$resolvedTemplate = VariableResolver::resolveTemplate($template, $context);
echo $resolvedTemplate; // Hello World!
```

## Unresolved Variables

Variables remaining after resolving a template are considered unresolved variables. An unresolved variable in a template indicates that you are missing one or more values in the context. This is probably not what you want.

A `UnresolvedVariableException` is thrown for the first unresolved variable.

```php
use webignition\Stubble\VariableResolver;

$resolver = new VariableResolver();

$template = 'Hello {{ name }} and welcome to {{ location }}.';
$context = [
    'name' => 'Jon',
];

try {
    $resolvedTemplate = $resolver->resolve($template, $context);
} catch (UnresolvedVariableException $exception) {
    // Do something useful ... logging?
    $exception->getVariable(); // 'location'
    $exception->getTemplate(); // 'Hello {{ name }} and welcome to {{ location }}.'
}
```

## Selectively Allowing Unresolved Variables

### Unresolved Variable Deciders

You may expect unresolved variables in resolved templates, for example if the content being generated is itself a template for use elsewhere.

A resolver instance can have one or more unresolved variable deciders, with each decider being a callable returning a boolean. The first decider to return true allows an unresolved variable to be present without an exception being thrown.

```php
use webignition\Stubble\VariableResolver;

$resolver = new VariableResolver();

$template = 'Hello {{ name }} and welcome to {{ location }}.';
$context = [
    'name' => 'Jon',
];

$resolver->addUnresolvedVariableDecider(function (string $variable) {
    return 'location' === $variable;
});

$resolvedTemplate = $resolver->resolve($template, $context);
echo $resolvedTemplate; // Hello Jon and welcome to {{ location }}.
```

### Use the DeciderFactory to Create Common Deciders

```php
use webignition\Stubble\DeciderFactory;
use webignition\Stubble\VariableResolver;

$resolver = new VariableResolver();

// Allow all unresolved variables
$resolver->addUnresolvedVariableDecider(DeciderFactory::createAllowAllDecider());

// Disallow all unresolved variables
$resolver->addUnresolvedVariableDecider(DeciderFactory::createDisallowAllDecider());

// Allow unresolved variables by pattern (regular expression)
$resolver->addUnresolvedVariableDecider(DeciderFactory::createAllowByPatternDecider('/^variable[0-9]$/'));


```
