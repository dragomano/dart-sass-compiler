<?php

declare(strict_types=1);

namespace DartSass\Loaders;

use DartSass\Exceptions\CompilationException;

use function array_map;
use function basename;
use function file_get_contents;
use function filter_var;
use function ltrim;
use function rtrim;

class HttpLoader implements LoaderInterface
{
    protected array $baseUrls;

    public function __construct(array $baseUrls)
    {
        $this->baseUrls = array_map(
            fn(string $url): string => rtrim($url, '/'),
            $baseUrls
        );
    }

    public function load(string $path): string
    {
        if ($this->isUrl($path) && ($content = $this->fetch($path)) !== null) {
            return $content;
        }

        foreach ($this->baseUrls as $baseUrl) {
            $fullUrl = $baseUrl . '/' . ltrim($path, '/');

            if (($content = $this->fetch($fullUrl)) !== null) {
                return $content;
            }

            $partial = $baseUrl . '/_' . basename($path);

            if (($content = $this->fetch($partial)) !== null) {
                return $content;
            }
        }

        throw new CompilationException("Failed to load SCSS from URL: $path");
    }

    protected function fetch(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'ignore_errors' => true,
            ]
        ]);

        $content = @file_get_contents($url, false, $context);

        return $content === false ? null : $content;
    }

    protected function isUrl(string $path): bool
    {
        return (bool) filter_var($path, FILTER_VALIDATE_URL);
    }
}
