<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Loaders\LoaderInterface;
use DartSass\Parsers\ParserFactory;

use function basename;
use function pathinfo;
use function str_starts_with;
use function substr;

use const PATHINFO_EXTENSION;

readonly class ModuleLoader
{
    public function __construct(
        private LoaderInterface $loader,
        private ParserFactory $parserFactory
    ) {}

    public function loadAst(string $path): array
    {
        $content = $this->loader->load($path);
        $parser  = $this->parserFactory->createFromPath($content, $path);

        return $parser->parse();
    }

    public function getNamespaceFromPath(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $basename  = basename($path, '.' . $extension);

        if (str_starts_with($basename, '_')) {
            return substr($basename, 1);
        }

        return $basename;
    }
}
