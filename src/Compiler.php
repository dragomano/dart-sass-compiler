<?php

declare(strict_types=1);

namespace DartSass;

use DartSass\Compilers\CompilerBuilder;
use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\CompilerEngineInterface;
use DartSass\Loaders\FileLoader;
use DartSass\Loaders\LoaderInterface;
use DartSass\Parsers\Syntax;

use function array_merge;

readonly class Compiler
{
    private CompilerEngineInterface $engine;

    public function __construct(array $options = [], ?LoaderInterface $loader = null)
    {
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

        $builder = new CompilerBuilder($options, $loader);

        $this->engine = $builder->build();
    }

    public function getContext(): CompilerContext
    {
        return $this->engine->getContext();
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
}
