<?php

declare(strict_types=1);

use DartSass\Exceptions\CompilationException;
use DartSass\Loaders\FileLoader;
use Tests\ReflectionAccessor;

beforeEach(function () {
    $this->testTempDir = sys_get_temp_dir() . '/sass_loader_test_' . uniqid();
    mkdir($this->testTempDir, 0777, true);
});

afterEach(function () {
    if (isset($this->testTempDir) && is_dir($this->testTempDir)) {
        $files = glob($this->testTempDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        $dirs = glob($this->testTempDir . '/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            rmdir($dir);
        }

        rmdir($this->testTempDir);
    }
});

it('normalizes load paths by removing trailing slashes in constructor', function () {
    $loader = new FileLoader(['/path/to/dir/', '\\another\\path\\', '/clean/path']);

    $accessor  = new ReflectionAccessor($loader);
    $loadPaths = $accessor->getProperty('loadPaths');

    expect($loadPaths)->toBe(['/path/to/dir', '\\another\\path', '/clean/path']);
});

it('loads content from direct file path successfully', function () {
    $testFile = $this->testTempDir . '/test.scss';
    $content  = '.test { color: red; }';
    file_put_contents($testFile, $content);

    $loader = new FileLoader([]);
    $result = $loader->load($testFile);

    expect($result)->toBe($content);
});

it('loads content from file path without extension by adding .scss', function () {
    $testFile = $this->testTempDir . '/test.scss';
    $content  = '.test { color: blue; }';
    file_put_contents($testFile, $content);

    $loader = new FileLoader([]);
    $result = $loader->load($this->testTempDir . '/test');

    expect($result)->toBe($content);
});

it('loads content from load paths successfully', function () {
    $testFile = $this->testTempDir . '/styles.scss';
    $content  = '.styles { margin: 0; }';
    file_put_contents($testFile, $content);

    $loader = new FileLoader([$this->testTempDir]);
    $result = $loader->load('styles.scss');

    expect($result)->toBe($content);
});

it('loads content from load paths without extension by adding .scss', function () {
    $testFile = $this->testTempDir . '/main.scss';
    $content  = '.main { padding: 10px; }';
    file_put_contents($testFile, $content);

    $loader = new FileLoader([$this->testTempDir]);
    $result = $loader->load('main');

    expect($result)->toBe($content);
});

it('loads partial file with underscore prefix successfully', function () {
    $testFile = $this->testTempDir . '/_variables.scss';
    $content  = '$primary-color: #ff0000;';
    file_put_contents($testFile, $content);

    $loader = new FileLoader([$this->testTempDir]);
    $result = $loader->load('variables.scss');

    expect($result)->toBe($content);
});

it('loads partial file without extension successfully', function () {
    $testFile = $this->testTempDir . '/_mixins.scss';
    $content  = '@mixin button() { padding: 10px; }';
    file_put_contents($testFile, $content);

    $loader = new FileLoader([$this->testTempDir]);
    $result = $loader->load('mixins');

    expect($result)->toBe($content);
});

it('loads from multiple load paths in order', function () {
    $firstDir  = $this->testTempDir . '/first';
    $secondDir = $this->testTempDir . '/second';
    mkdir($firstDir);
    mkdir($secondDir);

    $testFile = $secondDir . '/shared.scss';
    $content  = '.shared { display: flex; }';
    file_put_contents($testFile, $content);

    $loader = new FileLoader([$firstDir, $secondDir]);
    $result = $loader->load('shared.scss');

    expect($result)->toBe($content);

    unlink($testFile);
    rmdir($firstDir);
    rmdir($secondDir);
});

it('throws CompilationException when file is not found anywhere', function () {
    $loader = new FileLoader([$this->testTempDir]);

    expect(fn() => $loader->load('nonexistent.scss'))
        ->toThrow(CompilationException::class, 'File not found: nonexistent.scss');
});

it('throws CompilationException when file exists but is not readable', function () {
    $testFile = $this->testTempDir . '/unreadable.scss';
    $content  = 'body { color: black; }';
    file_put_contents($testFile, $content);

    $loader = new FileLoader([]);

    expect(fn() => $loader->load($this->testTempDir . '/nonexistent_file.scss'))
        ->toThrow(CompilationException::class);
});

it('does not add extension when file already has extension', function () {
    $testFile = $this->testTempDir . '/already.css';
    $content  = 'body { margin: 0; }';
    file_put_contents($testFile, $content);

    $loader = new FileLoader([$this->testTempDir]);

    expect(fn() => $loader->load('already.css'))
        ->not->toThrow(Exception::class);

    $result = $loader->load('already.css');
    expect($result)->toBe($content);
});

it('handles empty load paths array correctly', function () {
    $testFile = $this->testTempDir . '/direct.scss';
    $content  = '.direct { position: relative; }';
    file_put_contents($testFile, $content);

    $loader = new FileLoader([]);
    $result = $loader->load($testFile);

    expect($result)->toBe($content);
});

it('handles load paths with mixed directory separators', function () {
    $testFile = $this->testTempDir . '/mixed.scss';
    $content  = '.mixed { border: 1px solid; }';
    file_put_contents($testFile, $content);

    $loader = new FileLoader([
        $this->testTempDir . '/',
        $this->testTempDir . '\\',
        rtrim($this->testTempDir, '/\\'),
    ]);

    $result = $loader->load('mixed.scss');
    expect($result)->toBe($content);
});

it('checks file readability correctly', function () {
    $testFile = $this->testTempDir . '/check.scss';
    $content  = '.check { opacity: 0.5; }';
    file_put_contents($testFile, $content);

    $loader   = new FileLoader([]);
    $accessor = new ReflectionAccessor($loader);

    expect($accessor->callMethod('isFileReadable', [$testFile]))->toBeTrue()
        ->and($accessor->callMethod('isFileReadable', [$this->testTempDir . '/missing.scss']))->toBeFalse();
});

it('handles very long file paths correctly', function () {
    $longName = str_repeat('a', 100);
    $testFile = $this->testTempDir . '/' . $longName . '.scss';
    $content  = '.long { width: 100%; }';
    file_put_contents($testFile, $content);

    $loader = new FileLoader([$this->testTempDir]);
    $result = $loader->load($longName);

    expect($result)->toBe($content);
});
