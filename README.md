# Dart Sass Compiler

![PHP](https://img.shields.io/badge/PHP-^8.2-blue.svg?style=flat)
[![Coverage Status](https://coveralls.io/repos/github/dragomano/dart-sass-compiler/badge.svg?branch=main)](https://coveralls.io/github/dragomano/dart-sass-compiler?branch=main)

## Features

- Sass/SCSS compilation to CSS
- No dependencies

---

## Requirements

- PHP >= 8.2

---

## Installation via Composer

```bash
composer require bugo/dart-sass-compiler
```

## Usage examples

### Compiling SCSS file

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use DartSass\Compiler;

$compiler = new Compiler([
    'loadPaths' => ['styles/'],
    'style'     => 'compressed',
    'sourceMap' => true,
]);

$css = $compiler->compileFile(__DIR__ . '/assets/app.scss');

file_put_contents(__DIR__ . '/assets/app.css', $css);

echo "CSS compiled!\n";
```

### Compiling SCSS from string

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use DartSass\Compiler;
use DartSass\Parsers\Syntax;

$compiler = new Compiler();

// Scss
$scss = <<<'SCSS'
@use 'sass:color';

$color: red;
body {
  color: $color;
}
footer {
  background: color.adjust(#6b717f, $red: 15);
}
SCSS;

$css = $compiler->compileString($scss);

var_dump($css);

// Sass
$sass = <<<'SASS'
@use 'sass:color';

$color: red;
body
  color: $color;
footer
  background: color.adjust(#6b717f, $red: 15);
SASS;

$css = $compiler->compileString($sass, Syntax::SASS);

var_dump($css);
```

## Comparison with other packages

See the [benchmark.md](benchmark.md) file for results.

## Found a bug?

Paste the problematic code into the [sandbox](https://sass-lang.com/playground/), copy and send the link.

## Want to add something?

Don't forget to test (`composer run test`) and lint/fix (`composer run check`, `composer run fix`) your code.

## Additional links

* https://sass-lang.com/documentation
* https://tc39.es/ecma426/
