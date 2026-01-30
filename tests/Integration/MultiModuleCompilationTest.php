<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler(['loadPaths' => [__DIR__ . '/fixtures/sass']]);
});

it('handles complex compilation with multiple module imports', function () {
    $css = $this->compiler->compileFile('portal.scss');

    $source = file_get_contents(__DIR__ . '/fixtures/output.css');

    expect($css)->toBe($source);
});
