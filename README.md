# Stubble
A minimal library to replace mustache-like variables in strings, using PHP.

## Installing

`composer require webignition/stubble`


## Hello {{ name }}! Example

```php
use webignition\Stubble\VariableResolver;
use webignition\StubbleResolvable\Resolvable;

// Using a VariableResolver instance
$resolver = new VariableResolver();

$resolvable = new Resolvable(
    'Hello {{ name }}!',
    [
        'name' => 'World',
    ]
);

$resolvedTemplate = $resolver->resolve($resolvable);
echo $resolvedTemplate; // Hello World!
```

## Unresolved Variables

Variables remaining after resolving a template are considered unresolved variables. An unresolved variable in a template indicates that you are missing one or more values in the context. This is probably not what you want.

A `UnresolvedVariableException` is thrown for the first unresolved variable.

```php
use webignition\Stubble\UnresolvedVariableException;
use webignition\Stubble\VariableResolver;
use webignition\StubbleResolvable\Resolvable;

$resolver = new VariableResolver();

$resolvable = new Resolvable(
    'Hello {{ name }} and welcome to {{ location }}.',
    [
        'name' => 'World',
    ]
);

try {
    $resolvedTemplate = $resolver->resolve($resolvable);
} catch (UnresolvedVariableException $exception) {
    // Do something useful ... logging?
    $exception->getVariable(); // 'location'
    $exception->getTemplate(); // 'Hello {{ name }} and welcome to {{ location }}.'
}
```

## Selectively Allowing Unresolved Variables

### Unresolved Variable Deciders

You may expect unresolved variables in resolved templates, for example if the content being generated is itself a template for use elsewhere.

Using a `UnresolvedVariableFinder`, a resolver can make use of one or more unresolved variable deciders, 
with each decider being a callable returning a boolean. The first decider to return true allows an 
unresolved variable to be present without throwing an exception.

```php
use webignition\Stubble\UnresolvedVariableFinder;
use webignition\Stubble\VariableResolver;
use webignition\StubbleResolvable\Resolvable;

$resolver = new VariableResolver(
    new UnresolvedVariableFinder([
        function (string $variable) {
            return 'location' === $variable;
        },        
    ])
);

$resolvable = new Resolvable(
    'Hello {{ name }} and welcome to {{ location }}.',
    [
        'name' => 'World',
    ]
);

$resolvedTemplate = $resolver->resolve($resolvable);
echo $resolvedTemplate; // Hello Jon and welcome to {{ location }}.
```

### Use the DeciderFactory to Create Common Deciders

```php
use webignition\Stubble\DeciderFactory;
use webignition\Stubble\VariableResolver;
use webignition\Stubble\UnresolvedVariableFinder;

$resolver = new VariableResolver(
    new UnresolvedVariableFinder([
        // Allow all unresolved variables
        DeciderFactory::createAllowAllDecider(),
        // Disallow all unresolved variables
        DeciderFactory::createDisallowAllDecider(),
        // Allow unresolved variables by pattern (regular expression),
        DeciderFactory::createAllowByPatternDecider('/^variable[0-9]$/')
    ])
);
```
