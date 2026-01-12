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

use const DIRECTORY_SEPARATOR;

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
        $extensions = ['', '.scss', '.sass', '.css'];

        foreach ($extensions as $ext) {
            $filePath = $path . $ext;
            if ($this->isFileReadable($filePath)) {
                return file_get_contents($filePath);
            }
        }

        foreach ($this->loadPaths as $dir) {
            $fullPath = $dir . DIRECTORY_SEPARATOR . $path;

            foreach ($extensions as $ext) {
                $filePath = $fullPath . $ext;
                if ($this->isFileReadable($filePath)) {
                    return file_get_contents($filePath);
                }
            }

            $partial = $dir . DIRECTORY_SEPARATOR . '_' . basename($path);

            foreach ($extensions as $ext) {
                $filePath = $partial . $ext;
                if ($this->isFileReadable($filePath)) {
                    return file_get_contents($filePath);
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
