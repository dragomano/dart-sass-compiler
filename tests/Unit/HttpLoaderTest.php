<?php

declare(strict_types=1);

use DartSass\Exceptions\CompilationException;
use DartSass\Loaders\HttpLoader;

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

    expect(fn () => $mockLoader->load('nonexistent.scss'))
        ->toThrow(CompilationException::class, 'Failed to load SCSS from URL: nonexistent.scss');
});

it('loads from multiple baseUrls in order', function () {
    $mockLoader = new class (['https://first.com', 'https://second.com']) extends HttpLoader {
        private array $mockResponses = [
            'https://second.com/test.scss' => 'content from second',
        ];

        protected function fetch(string $url): ?string
        {
            return $this->mockResponses[$url] ?? null;
        }
    };

    $content = $mockLoader->load('test.scss');

    expect($content)->toBe('content from second');
});

it('recognizes valid HTTP URLs', function () {
    $mockLoader = new class ([]) extends HttpLoader {
        public function testIsUrl(string $url): bool
        {
            return $this->isUrl($url);
        }
    };

    expect($mockLoader->testIsUrl('https://example.com/styles.css'))->toBeTrue()
        ->and($mockLoader->testIsUrl('http://example.com/styles.css'))->toBeTrue();
});

it('recognizes valid HTTPS URLs', function () {
    $mockLoader = new class ([]) extends HttpLoader {
        public function testIsUrl(string $url): bool
        {
            return $this->isUrl($url);
        }
    };

    expect($mockLoader->testIsUrl('https://example.com/styles.css'))->toBeTrue();
});

it('does not recognize invalid URLs without scheme', function () {
    $mockLoader = new class ([]) extends HttpLoader {
        public function testIsUrl(string $url): bool
        {
            return $this->isUrl($url);
        }
    };

    expect($mockLoader->testIsUrl('styles.css'))->toBeFalse()
        ->and($mockLoader->testIsUrl('/path/to/styles.css'))->toBeFalse()
        ->and($mockLoader->testIsUrl('./styles.css'))->toBeFalse();
});

it('does not recognize invalid URLs with malformed schemes', function () {
    $mockLoader = new class ([]) extends HttpLoader {
        public function testIsUrl(string $url): bool
        {
            return $this->isUrl($url);
        }
    };

    expect($mockLoader->testIsUrl('://example.com/styles.css'))->toBeFalse()
        ->and($mockLoader->testIsUrl('invalid url with spaces'))->toBeFalse();
});

it('handles multiple baseUrls with trailing slashes correctly', function () {
    $mockLoader = new class (['https://example.com/', 'https://cdn.com/api/']) extends HttpLoader {
        private array $mockResponses = [
            'https://example.com/test.scss' => 'from first base',
        ];

        protected function fetch(string $url): ?string
        {
            return $this->mockResponses[$url] ?? null;
        }
    };

    $content = $mockLoader->load('test.scss');

    expect($content)->toBe('from first base');
});

it('loads direct URL even when baseUrls are provided', function () {
    $mockLoader = new class (['https://base.com']) extends HttpLoader {
        private array $mockResponses = [
            'https://direct.com/file.scss' => 'direct content',
        ];

        protected function fetch(string $url): ?string
        {
            return $this->mockResponses[$url] ?? null;
        }
    };

    $content = $mockLoader->load('https://direct.com/file.scss');

    expect($content)->toBe('direct content');
});

it('handles empty baseUrls array', function () {
    $mockLoader = new class ([]) extends HttpLoader {
        private array $mockResponses = [
            'https://example.com/test.scss' => 'content',
        ];

        protected function fetch(string $url): ?string
        {
            return $this->mockResponses[$url] ?? null;
        }
    };

    $content = $mockLoader->load('https://example.com/test.scss');

    expect($content)->toBe('content');
});

it('throws exception when loading from empty baseUrls with invalid path', function () {
    $mockLoader = new class ([]) extends HttpLoader {
        protected function fetch(string $url): ?string
        {
            return null;
        }
    };

    expect(fn () => $mockLoader->load('local.scss'))
        ->toThrow(CompilationException::class, 'Failed to load SCSS from URL: local.scss');
});
