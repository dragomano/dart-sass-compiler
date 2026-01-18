<?php

declare(strict_types=1);

use DartSass\Utils\SourceMapGenerator;
use Tests\ReflectionAccessor;

beforeEach(function () {
    $this->generator = new SourceMapGenerator();
    $this->accessor  = new ReflectionAccessor($this->generator);
});

describe('source map generation', function () {
    it('provides backward compatibility without options', function () {
        $mappings = [
            [
                'generated' => ['line' => 1, 'column' => 0],
                'original'  => ['line' => 1, 'column' => 0],
            ],
        ];

        $result = $this->generator->generate($mappings, 'input.scss', 'output.css');

        $sourceMap = json_decode($result, true);

        expect($sourceMap)->toHaveKey('version')
            ->and($sourceMap)->toHaveKey('sources')
            ->and($sourceMap)->toHaveKey('mappings')
            ->and($sourceMap)->not->toHaveKey('sourcesContent');
    });
})->covers(SourceMapGenerator::class);

describe('mappings generation', function () {
    it('adds comma between multiple mappings on the same line', function () {
        $mappings = [
            [
                'generated'   => ['line' => 1, 'column' => 0],
                'original'    => ['line' => 1, 'column' => 0],
                'sourceIndex' => 0,
            ],
            [
                'generated'   => ['line' => 1, 'column' => 10],
                'original'    => ['line' => 1, 'column' => 10],
                'sourceIndex' => 0,
            ],
        ];

        $result = $this->generator->generate($mappings, 'source.scss', 'output.css');
        $sourceMap = json_decode($result, true);

        expect($sourceMap)->toBeArray()
            ->and($sourceMap['mappings'])->toContain(',');
    });
})->covers(SourceMapGenerator::class);

describe('source map options', function () {
    it('checks that SourceMapGenerator with includeSources false does not include sourcesContent', function () {
        $mappings = [
            [
                'generated'   => ['line' => 1, 'column' => 0],
                'original'    => ['line' => 1, 'column' => 0],
                'sourceIndex' => 0,
            ],
        ];

        $result = $this->generator->generate($mappings, 'source.scss', 'output.css', ['includeSources' => false]);
        $sourceMap = json_decode($result, true);

        expect($sourceMap)->toBeArray()
            ->and($sourceMap)->not->toHaveKey('sourcesContent');
    });

    it('checks that SourceMapGenerator with includeSources true includes sourcesContent', function () {
        $mappings = [
            [
                'generated'   => ['line' => 1, 'column' => 0],
                'original'    => ['line' => 1, 'column' => 0],
                'sourceIndex' => 0,
            ],
        ];

        $result = $this->generator->generate($mappings, 'source.scss', 'output.css', [
            'includeSources' => true,
            'sourceContent'  => 'body { color: red; }',
        ]);
        $sourceMap = json_decode($result, true);

        expect($sourceMap)->toBeArray()
            ->and($sourceMap)->toHaveKey('sourcesContent')
            ->and($sourceMap['sourcesContent'])->toBe(['body { color: red; }']);
    });
})->covers(SourceMapGenerator::class);

describe('VLQ encoding', function () {
    it('uses VLQ continuation bit for large values', function () {
        $encoded = $this->accessor->callMethod('encodeVLQ', [100]);

        expect(strlen($encoded))->toBeGreaterThan(1);
    });

    it('does not use VLQ continuation bit for small values', function () {
        $encoded = $this->accessor->callMethod('encodeVLQ', [0]);

        expect(strlen($encoded))->toBe(1);
    });

    it('does not use VLQ continuation bit for other small values', function () {
        $encoded1  = $this->accessor->callMethod('encodeVLQ', [1]);
        $encoded15 = $this->accessor->callMethod('encodeVLQ', [15]);

        expect(strlen($encoded1))->toBe(1)
            ->and(strlen($encoded15))->toBe(1);
    });
})->covers(SourceMapGenerator::class);
