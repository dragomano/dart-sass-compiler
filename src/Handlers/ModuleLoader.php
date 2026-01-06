<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Loaders\LoaderInterface;
use DartSass\Parsers\ParserFactory;

use function basename;
use function ltrim;
use function pathinfo;

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

        return ltrim($basename, '_');
    }
}
