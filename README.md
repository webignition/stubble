# Stubble
A minimal library to replace mustache-like variables in strings, using PHP.

## Installing

`composer require webignition/stubble`


## Hello {{ name }}! Example

```php
use webignition\Stubble\VariableResolver;

$resolver = new VariableResolver();

$template = 'Hello {{ name }}!';
$context = [
    'name' => 'World',
];

$resolvedTemplate = $resolver->resolve($template, $context);
// Hello World!
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
