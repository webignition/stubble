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
