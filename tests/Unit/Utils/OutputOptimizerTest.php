<?php

declare(strict_types=1);

use DartSass\Utils\OutputOptimizer;

dataset('css input and expected output', [
    'simple property with zero unit' => [
        '.test { width: 0px; }',
        '.test { width: 0; }',
    ],

    'multiple properties with zero units' => [
        '.test { width: 0px; height: 0em; margin: 0pt; padding: 0pc; }',
        '.test { width: 0; height: 0; margin: 0; padding: 0; }',
    ],

    'mixed zero and non-zero values' => [
        '.test { width: 0px; height: 10px; margin: 0em; padding: 5px; }',
        '.test { width: 0; height: 10px; margin: 0; padding: 5px; }',
    ],

    'zero values without units' => [
        '.test { width: 0; height: 0; }',
        '.test { width: 0; height: 0; }',
    ],

    'complex values' => [
        '.test { background: linear-gradient(to right, red, blue); width: 0px; }',
        '.test { background: linear-gradient(to right, red, blue); width: 0; }',
    ],

    'calc functions' => [
        '.test { width: calc(100% - 0px); height: calc(50vh + 0em); }',
        '.test { width: calc(100% - 0px); height: calc(50vh + 0em); }',
    ],

    'important declarations' => [
        '.test { width: 0px !important; height: 10px !important; }',
        '.test { width: 0 !important; height: 10px !important; }',
    ],

    'properties without colons' => [
        '.test { width: 10px; some random text; height: 20px; }',
        '.test { width: 10px; some random text; height: 20px; }',
    ],

    'empty blocks' => [
        '.test { }',
        '.test { }',
    ],

    'multiple blocks' => [
        '.first { width: 10px; } .second { height: 0px; }',
        '.first { width: 10px; } .second { height: 0; }',
    ],

    'at-rules keep units inside' => [
        '@media (min-width: 768px) { .test { width: 0px; } }',
        '@media (min-width: 768px) { .test { width: 0; } }',
    ],

    'font-face rule' => [
        '@font-face { font-family: Test; src: url(test.woff); }',
        '@font-face { font-family: Test; src: url(test.woff); }',
    ],

    'nested structure' => [
        '.parent { width: 0px; .child { height: 0em; } }',
        '.parent { width: 0; .child { height: 0; } }',
    ],

    'curly braces in values' => [
        '.test { content: "}"; width: 0px; }',
        '.test { content: "}"; width: 0; }',
    ],

    'empty CSS' => [
        '',
        '',
    ],
]);

dataset('compressed style cases', [
    'simple compressed' => [
        '.test { width: 10px; height: 0px; }',
        '.test{width:10px;height:0}',
    ],

    'removes comments' => [
        '/* comment */ .test { /* another */ width: 10px; }',
        '.test{width:10px}',
    ],

    'removes extra spaces' => [
        '.test  {  width  :  10px  ;  }',
        '.test{width:10px}',
    ],

    'removes line breaks' => [
        ".test {\n  width: 10px;\n  height: 20px;\n}",
        '.test{width:10px;height:20px}',
    ],

    'handles multiple selectors' => [
        '.test, .other { width: 0px; }',
        '.test,.other{width:0}',
    ],

    'removes spaces around punctuation' => [
        '.test { width : 10px ; height : 20px ; }',
        '.test{width:10px;height:20px}',
    ],

    'empty blocks in compressed' => [
        '.test { } .other { width: 10px; }',
        '.test{}.other{width:10px}',
    ],

    'preserves sourceMappingURL comments' => [
        '.test { width: 10px; } /*# sourceMappingURL=style.css.map */',
        '.test{width:10px}/*# sourceMappingURL=style.css.map */',
    ],

    'removes regular comments but preserves sourceMappingURL' => [
        '/* regular comment */ .test { /* another comment */ width: 10px; } /*# sourceMappingURL=style.css.map */',
        '.test{width:10px}/*# sourceMappingURL=style.css.map */',
    ],

    'preserves important comments' => [
        '/*! important comment */ .test { width: 10px; }',
        '/*! important comment */ .test{width:10px}',
    ],

    'removes regular comments but preserves important comments' => [
        '/* regular comment */ .test { width: 10px; } /*! important comment */',
        '.test{width:10px}/*! important comment */',
    ],
]);

dataset('expanded style cases', [
    'simple expanded' => [
        '.test { width: 10px; height: 0px; }',
        '.test { width: 10px; height: 0; }',
    ],

    'multiple selectors' => [
        '.test, .other { width: 0px; }',
        '.test, .other { width: 0; }',
    ],

    'nested rules' => [
        '.parent { .child { width: 0px; } }',
        '.parent { .child { width: 0; } }',
    ],

    'complex properties' => [
        '.test { background: linear-gradient(to right, red, blue); width: 0px; }',
        '.test { background: linear-gradient(to right, red, blue); width: 0; }',
    ],
]);

dataset('redundant properties cases', [
    'keeps all safe properties' => [
        '.test { width: 10px; width: 20px; height: 30px; }',
        '.test { width: 10px; width: 20px; height: 30px; }',
    ],

    'keeps all unsafe properties' => [
        '.test { background: red; background: blue; filter: blur(5px); filter: blur(10px); }',
        '.test { background: red; background: blue; filter: blur(5px); filter: blur(10px); }',
    ],
]);

dataset('complex scenarios', [
    'pseudo-selectors keep units' => [
        '.test:hover, .other:focus { width: 0px; }',
        '.test:hover, .other:focus { width: 0; }',
    ],

    'keyframes keep units inside' => [
        '@keyframes slideIn { 0% { transform: translateX(0px); } 100% { transform: translateX(100%); } }',
        '@keyframes slideIn { 0% { transform: translateX(0px); } 100% { transform: translateX(100%); } }',
    ],

    'consecutive commas in selector' => [
        '.test,, .other { width: 0px; }',
        '.test, .other { width: 0; }',
    ],

    'CSS with whitespace only' => [
        "   \n   \t   ",
        "\n\n",
    ],
]);

it('optimizes zero units for all CSS', function (string $input, string $expected) {
    $optimizer = new OutputOptimizer('expanded');

    $result = $optimizer->optimize($input);

    expect($result)->toEqualCss($expected);
})->with('css input and expected output');

it('optimizes compressed style', function (string $input, string $expected) {
    $optimizer = new OutputOptimizer('compressed');

    $result = $optimizer->optimize($input);

    expect($result)->toEqualCss($expected);
})->with('compressed style cases');

it('optimizes expanded style', function (string $input, string $expected) {
    $optimizer = new OutputOptimizer('expanded');

    $result = $optimizer->optimize($input);

    expect($result)->toEqualCss($expected);
})->with('expanded style cases');

it('handles redundant properties correctly', function (string $input, string $expected) {
    $optimizer = new OutputOptimizer('expanded');

    $result = $optimizer->optimize($input);

    expect($result)->toEqualCss($expected);
})->with('redundant properties cases');

it('handles complex scenarios', function (string $input, string $expected) {
    $optimizer = new OutputOptimizer('expanded');

    $result = $optimizer->optimize($input);

    expect($result)->toEqualCss($expected);
})->with('complex scenarios');

it('initializes with different styles', function () {
    $expandedOptimizer   = new OutputOptimizer('expanded');
    $compressedOptimizer = new OutputOptimizer('compressed');

    expect($expandedOptimizer)->toBeInstanceOf(OutputOptimizer::class)
        ->and($compressedOptimizer)->toBeInstanceOf(OutputOptimizer::class);
});

it('preserves calc functions with zero units', function () {
    $css       = '.test { width: calc(100% - 0px); }';
    $expected  = '.test { width: calc(100% - 0px); }';
    $optimizer = new OutputOptimizer('expanded');

    $result = $optimizer->optimize($css);

    expect($result)->toEqualCss($expected);
});

it('handles properties with complex values and zero units', function () {
    $css       = '.test { background: url(image.png) no-repeat 0px 0px; width: 0px; }';
    $expected  = '.test { background: url(image.png) no-repeat 0 0; width: 0; }';
    $optimizer = new OutputOptimizer('expanded');

    $result = $optimizer->optimize($css);

    expect($result)->toEqualCss($expected);
});

it('returns CSS unchanged for unsupported styles', function () {
    $css       = '.test { width: 10px; height: 20px; }';
    $optimizer = new OutputOptimizer('unsupported');

    $result = $optimizer->optimize($css);

    expect($result)->toEqualCss($css);
});

it('keeps non-property lines inside declaration blocks', function () {
    $css = /** @lang text */ <<<'CSS'
    .test {
      some random text;
      width: 10px;
    }
    CSS;

    $optimizer = new OutputOptimizer('expanded');
    $result = $optimizer->optimize($css);

    expect($result)->toContain('some random text;');
});
