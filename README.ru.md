# Dart Sass Compiler

![PHP](https://img.shields.io/badge/PHP-^8.2-blue.svg?style=flat)
[![Coverage Status](https://coveralls.io/repos/github/dragomano/dart-sass-compiler/badge.svg?branch=main)](https://coveralls.io/github/dragomano/dart-sass-compiler?branch=main)

## Особенности

- Компиляция Sass/SCSS в CSS
- Отсутствие зависимостей

---

## Требования

- PHP >= 8.2

---

## Установка через Composer

```bash
composer require bugo/dart-sass-compiler
```

## Примеры использования

### Компиляция файла SCSS

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

echo "CSS скомпилирован!\n";
```

### Компиляция SCSS из строки

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use DartSass\Compiler;

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

## Сравнение с другими пакетами

Смотрите файл [benchmark.md](benchmark.md) для просмотра результатов.

## Нашли ошибку?

Вставьте проблемный код в [песочницу](https://sass-lang.com/playground/), скопируйте и пришлите ссылку.

## Хотите что-то добавить?

Не забудьте протестировать (`composer run tests`) и привести в порядок (`composer run check`, `composer run fix`) свой код.

## Дополнительные ресурсы

* https://dragomano.github.io/dart-sass-docs-russian/
* https://tc39.es/ecma426/
