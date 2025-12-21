<?php declare(strict_types=1);

use DartSass\Compiler;
use DartSass\Utils\SourceMapGenerator;

it('generates valid source map when enabled', function () {
    $scss = <<<'SCSS'
    body {
        color: blue;
        font-size: 16px;
    }
    SCSS;

    $compiler = new Compiler(['style' => 'expanded', 'sourceMap' => true]);
    $output = $compiler->compileString($scss);

    expect($output)->toContain('sourceMappingURL');

    $sourceMap = json_decode(file_get_contents('output.css.map'), true);

    expect($sourceMap)->toHaveKey('version')
        ->and($sourceMap['version'])->toBe(3)
        ->and($sourceMap)->toHaveKey('sources')
        ->and($sourceMap)->toHaveKey('mappings');
});

it('checks that SourceMapGenerator with includeSources false does not include sourcesContent', function () {
    $generator = new SourceMapGenerator();
    $mappings = [
        ['generated' => ['line' => 1, 'column' => 0], 'original' => ['line' => 1, 'column' => 0]],
    ];

    $result = $generator->generate($mappings, 'input.scss', 'output.css', ['includeSources' => false]);

    $sourceMap = json_decode($result, true);

    expect($sourceMap)->not->toHaveKey('sourcesContent');
});

it('checks that SourceMapGenerator with includeSources true includes sourcesContent', function () {
    $generator = new SourceMapGenerator();
    $mappings = [
        ['generated' => ['line' => 1, 'column' => 0], 'original' => ['line' => 1, 'column' => 0]],
    ];
    $sourceContent = 'body { color: red; }';

    $result = $generator->generate($mappings, 'input.scss', 'output.css', [
        'includeSources' => true,
        'sourceContent' => $sourceContent,
    ]);

    $sourceMap = json_decode($result, true);

    expect($sourceMap)->toHaveKey('sourcesContent')
        ->and($sourceMap['sourcesContent'])->toBe([$sourceContent]);
});

it('provides backward compatibility without options', function () {
    $generator = new SourceMapGenerator();
    $mappings = [
        ['generated' => ['line' => 1, 'column' => 0], 'original' => ['line' => 1, 'column' => 0]],
    ];

    $result = $generator->generate($mappings, 'input.scss', 'output.css');

    $sourceMap = json_decode($result, true);

    expect($sourceMap)->toHaveKey('version')
        ->and($sourceMap)->toHaveKey('sources')
        ->and($sourceMap)->toHaveKey('mappings')
        ->and($sourceMap)->not->toHaveKey('sourcesContent');
});

it('compiles with custom includeSources option', function () {
    $scss = 'body { color: blue; }';
    $compiler = new Compiler(['sourceMap' => true, 'includeSources' => true]);
    $output = $compiler->compileString($scss);

    expect($output)->toContain('sourceMappingURL');

    $sourceMap = json_decode(file_get_contents('output.css.map'), true);

    expect($sourceMap)->toHaveKey('sourcesContent')
        ->and($sourceMap['sourcesContent'])->toBe([$scss]);
});

it('compiles with custom includeSources false option', function () {
    $scss = 'body { color: blue; }';
    $compiler = new Compiler(['sourceMap' => true, 'includeSources' => false]);
    $output = $compiler->compileString($scss);

    expect($output)->toContain('sourceMappingURL');

    $sourceMap = json_decode(file_get_contents('output.css.map'), true);

    expect($sourceMap)->not->toHaveKey('sourcesContent');
});

it('compiles with custom sourceFile option', function () {
    $scss = 'body { color: blue; }';
    $customSourceFile = 'custom-input.scss';
    $compiler = new Compiler(['sourceMap' => true, 'sourceFile' => $customSourceFile]);
    $compiler->compileString($scss);

    $sourceMap = json_decode(file_get_contents('output.css.map'), true);

    expect($sourceMap['sources'])->toBe([$customSourceFile]);
});

it('compiles with custom sourceMapFilename option', function () {
    $scss = 'body { color: blue; }';
    $customMapFile = 'custom-output.css.map';
    $compiler = new Compiler(['sourceMap' => true, 'sourceMapFilename' => $customMapFile]);
    $output = $compiler->compileString($scss);

    expect($output)->toContain("sourceMappingURL=$customMapFile");
});

it('compiles with custom outputFile option', function () {
    $scss = 'body { color: blue; }';
    $customOutputFile = 'custom-output.css';
    $compiler = new Compiler(['sourceMap' => true, 'outputFile' => $customOutputFile]);
    $compiler->compileString($scss);

    $sourceMap = json_decode(file_get_contents('output.css.map'), true);

    expect($sourceMap['file'])->toBe($customOutputFile);
});

it('validates internal mappings for simple SCSS', function () {
    $scss = <<<'SCSS'
body {
  color: blue;
  margin: 10px;
}
SCSS;

    $compiler = new Compiler(['sourceMap' => true]);
    $compiler->compileString($scss);

    $expected = [
        [
          'generated' => ['line' => 1, 'column' => 0],
          'original'  => ['line' => 1, 'column' => 0],
          'sourceIndex' => 0,
        ], // body {
        [
          'generated' => ['line' => 2, 'column' => 2],
          'original'  => ['line' => 2, 'column' => 2],
          'sourceIndex' => 0,
        ], // color: blue;
        [
          'generated' => ['line' => 3, 'column' => 2],
          'original'  => ['line' => 3, 'column' => 2],
          'sourceIndex' => 0,
        ], // margin: 10px;
    ];

    expect($compiler->getMappings())->toBe($expected);
});
