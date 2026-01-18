<?php

declare(strict_types=1);

use DartSass\Compiler;

describe('Compiler', function () {
    it('returns options passed during initialization', function () {
        $customOptions = [
            'style'     => 'compressed',
            'sourceMap' => true,
        ];

        $compiler = new Compiler($customOptions);
        $options = $compiler->getContext()->options;

        expect($options)->toBeArray()
            ->and($options['style'])->toBe('compressed')
            ->and($options['sourceMap'])->toBeTrue();
    });

    it('returns default options when no options passed', function () {
        $compiler = new Compiler();
        $options  = $compiler->getContext()->options;

        expect($options)->toBeArray()
            ->and($options['style'])->toBe('expanded')
            ->and($options['sourceMap'])->toBeFalse();
    });

    it('generates valid source map when enabled', function () {
        $scss = <<<'SCSS'
        body {
            color: blue;
            font-size: 16px;
        }
        SCSS;

        $compiler = new Compiler(['style' => 'expanded', 'sourceMap' => true]);
        $output   = $compiler->compileString($scss);

        expect($output)->toContain('sourceMappingURL');

        $sourceMap = json_decode(file_get_contents('output.css.map'), true);

        expect($sourceMap)->toHaveKey('version')
            ->and($sourceMap['version'])->toBe(3)
            ->and($sourceMap)->toHaveKey('sources')
            ->and($sourceMap)->toHaveKey('mappings');
    });

    it('compiles with custom includeSources true option', function () {
        $scss     = 'body { color: blue; }';
        $compiler = new Compiler(['sourceMap' => true, 'includeSources' => true]);
        $output   = $compiler->compileString($scss);

        expect($output)->toContain('sourceMappingURL');

        $sourceMap = json_decode(file_get_contents('output.css.map'), true);

        expect($sourceMap)->toHaveKey('sourcesContent')
            ->and($sourceMap['sourcesContent'])->toBe([$scss]);
    });

    it('compiles with custom includeSources false option', function () {
        $scss     = 'body { color: blue; }';
        $compiler = new Compiler(['sourceMap' => true, 'includeSources' => false]);
        $output   = $compiler->compileString($scss);

        expect($output)->toContain('sourceMappingURL');

        $sourceMap = json_decode(file_get_contents('output.css.map'), true);

        expect($sourceMap)->not->toHaveKey('sourcesContent');
    });

    it('compiles with custom sourceFile option', function () {
        $scss       = 'body { color: blue; }';
        $sourceFile = 'custom-input.scss';
        $compiler   = new Compiler(['sourceMap' => true, 'sourceFile' => $sourceFile]);
        $compiler->compileString($scss);

        $sourceMap = json_decode(file_get_contents('output.css.map'), true);

        expect($sourceMap['sources'])->toBe([$sourceFile]);
    });

    it('compiles with custom sourceMapFile option', function () {
        $scss     = 'body { color: blue; }';
        $mapFile  = 'custom-output.css.map';
        $compiler = new Compiler(['sourceMap' => true, 'sourceMapFile' => $mapFile]);
        $output   = $compiler->compileString($scss);

        expect($output)->toContain("sourceMappingURL=$mapFile");
    });

    it('compiles with custom outputFile option', function () {
        $scss       = 'body { color: blue; }';
        $outputFile = 'custom-output.css';
        $compiler   = new Compiler(['sourceMap' => true, 'outputFile' => $outputFile]);
        $compiler->compileString($scss);

        $sourceMap = json_decode(file_get_contents('output.css.map'), true);

        expect($sourceMap['file'])->toBe($outputFile);
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
                'generated'   => ['line' => 1, 'column' => 0],
                'original'    => ['line' => 1, 'column' => 0],
                'sourceIndex' => 0,
            ], // body {
            [
                'generated'   => ['line' => 2, 'column' => 2],
                'original'    => ['line' => 2, 'column' => 2],
                'sourceIndex' => 0,
            ], // color: blue;
            [
                'generated'   => ['line' => 3, 'column' => 2],
                'original'    => ['line' => 3, 'column' => 2],
                'sourceIndex' => 0,
            ], // margin: 10px;
        ];

        expect($compiler->getContext()->mappings)->toBe($expected);
    });
});
