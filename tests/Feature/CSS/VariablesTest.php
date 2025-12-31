<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

it('allows custom function definition with @return', function () {
    $scss = <<<'SCSS'
    :root {
        --bg-image: url('../images/background.jpg');
        --icon-check: url('data:image/svg+xml;utf8,<svg>...</svg>');
    }

    .using-css-vars {
        background-image: var(--bg-image);
    }

    .checkbox::before {
        content: var(--icon-check);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    :root {
      --bg-image: url("../images/background.jpg");
      --icon-check: url("data:image/svg+xml;utf8,<svg>...</svg>");
    }
    .using-css-vars {
      background-image: var(--bg-image);
    }
    .checkbox::before {
      content: var(--icon-check);
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});
