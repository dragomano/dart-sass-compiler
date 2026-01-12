<?php

declare(strict_types=1);

use DartSass\Exceptions\CompilationException;
use DartSass\Loaders\HttpLoader;
use Tests\ReflectionAccessor;

it('normalizes baseUrls by removing trailing slashes in constructor', function () {
    $loader = new HttpLoader(['https://example.com/', 'https://cdn.com/path/']);

    expect($loader)->toBeInstanceOf(HttpLoader::class);

    $mockLoader = new class (['https://example.com/', 'https://cdn.com/path/']) extends HttpLoader {
        public function getBaseUrls(): array
        {
            return $this->baseUrls;
        }
    };

    expect($mockLoader->getBaseUrls())->toBe(['https://example.com', 'https://cdn.com/path']);
});

it('loads content from direct URL successfully', function () {
    $mockLoader = new class (['https://example.com']) extends HttpLoader {
        private array $mockResponses = [
            'https://example.com/test.scss' => '.test { color: red; }',
        ];

        protected function fetch(string $url): ?string
        {
            return $this->mockResponses[$url] ?? null;
        }
    };

    $content = $mockLoader->load('https://example.com/test.scss');

    expect($content)->toBe('.test { color: red; }');
});

it('loads content through baseUrl + path successfully', function () {
    $mockLoader = new class (['https://example.com']) extends HttpLoader {
        private array $mockResponses = [
            'https://example.com/styles/main.scss' => '.main { display: block; }',
        ];

        protected function fetch(string $url): ?string
        {
            return $this->mockResponses[$url] ?? null;
        }
    };

    $content = $mockLoader->load('styles/main.scss');

    expect($content)->toBe('.main { display: block; }');
});

it('loads partial file with underscore prefix successfully', function () {
    $mockLoader = new class (['https://example.com']) extends HttpLoader {
        private array $mockResponses = [
            'https://example.com/_colors.scss' => '$primary: #ff0000;',
        ];

        protected function fetch(string $url): ?string
        {
            return $this->mockResponses[$url] ?? null;
        }
    };

    $content = $mockLoader->load('colors.scss');

    expect($content)->toBe('$primary: #ff0000;');
});

it('throws CompilationException when resource is not available', function () {
    $mockLoader = new class (['https://example.com']) extends HttpLoader {
        protected function fetch(string $url): ?string
        {
            return null; // Always fail
        }
    };

    expect(fn() => $mockLoader->load('nonexistent.scss'))
        ->toThrow(CompilationException::class, 'Failed to load SCSS from URL: nonexistent.scss');
});

it('tests fetch method directly using ReflectionAccessor', function () {
    $loader   = new HttpLoader(['https://example.com']);
    $accessor = new ReflectionAccessor($loader);

    $result = $accessor->callMethod('fetch', ['https://httpbin.org/json']);
    expect($result)->toBeString()->and($result)->not->toBeEmpty();

    $emptyResult = $accessor->callMethod('fetch', ['https://httpbin.org/status/204']);
    expect($emptyResult)->toBeString();
});
