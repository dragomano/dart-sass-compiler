<?php

declare(strict_types=1);

namespace DartSass;

use DartSass\Compilers\CompilerBuilder;
use DartSass\Compilers\CompilerEngineInterface;
use DartSass\Loaders\FileLoader;
use DartSass\Loaders\LoaderInterface;
use DartSass\Parsers\Syntax;
use DartSass\Utils\LoggerInterface;

use function array_merge;

readonly class Compiler
{
    private CompilerEngineInterface $engine;

    public function __construct(
        array $options = [],
        ?LoaderInterface $loader = null,
        ?LoggerInterface $logger = null
    ) {
        $options = array_merge(
            [
                'style'          => 'expanded',
                'sourceMap'      => false,
                'includeSources' => false,
                'loadPaths'      => [],
                'sourceFile'     => 'input.scss',
                'sourceMapFile'  => 'output.css.map',
                'outputFile'     => 'output.css',
            ],
            $options
        );

        $loader ??= new FileLoader($options['loadPaths']);

        $builder = new CompilerBuilder($options, $loader, $logger);

        $this->engine = $builder->build();
    }

    public function compileString(string $string, ?Syntax $syntax = null): string
    {
        return $this->engine->compileString($string, $syntax);
    }

    public function compileFile(string $filePath): string
    {
        return $this->engine->compileFile($filePath);
    }

    public function addFunction(string $name, callable $callback): void
    {
        $this->engine->addFunction($name, $callback);
    }

    public function getOptions(): array
    {
        return $this->engine->getOptions();
    }

    public function getMappings(): array
    {
        return $this->engine->getMappings();
    }
}
