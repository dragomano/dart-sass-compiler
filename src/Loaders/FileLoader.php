<?php

declare(strict_types=1);

namespace DartSass\Loaders;

use DartSass\Exceptions\CompilationException;

use function array_map;
use function basename;
use function file_exists;
use function file_get_contents;
use function is_readable;
use function rtrim;
use function str_contains;

class FileLoader implements LoaderInterface
{
    public function __construct(private array $loadPaths)
    {
        $this->loadPaths = array_map(
            fn(string $path): string => rtrim($path, '/\\'),
            $loadPaths,
        );
    }

    public function load(string $path): string
    {
        if ($this->isFileReadable($path)) {
            return file_get_contents($path);
        }

        if (! str_contains($path, '.')) {
            $pathWithExtension = $path . '.scss';
            if ($this->isFileReadable($pathWithExtension)) {
                return file_get_contents($pathWithExtension);
            }
        }

        foreach ($this->loadPaths as $dir) {
            $fullPath = $dir . DIRECTORY_SEPARATOR . $path;

            if ($this->isFileReadable($fullPath)) {
                return file_get_contents($fullPath);
            }

            if (! str_contains($path, '.')) {
                $fullPathWithExt = $fullPath . '.scss';
                if ($this->isFileReadable($fullPathWithExt)) {
                    return file_get_contents($fullPathWithExt);
                }
            }

            $partial = $dir . DIRECTORY_SEPARATOR . '_' . basename($path);

            if ($this->isFileReadable($partial)) {
                return file_get_contents($partial);
            }

            if (! str_contains($path, '.')) {
                $partialWithExt = $partial . '.scss';
                if ($this->isFileReadable($partialWithExt)) {
                    return file_get_contents($partialWithExt);
                }
            }
        }

        throw new CompilationException("File not found: $path");
    }

    private function isFileReadable(string $path): bool
    {
        return file_exists($path) && is_readable($path);
    }
}
