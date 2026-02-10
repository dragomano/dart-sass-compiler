<?php

declare(strict_types=1);

namespace Benchmarks;

use Bugo\Sass\Compiler as EmbeddedCompiler;
use DartSass\Compiler as SassCompiler;
use Random\RandomException;
use ScssPhp\ScssPhp\Compiler as ScssCompiler;
use ScssPhp\ScssPhp\Exception\SassException;
use ScssPhp\ScssPhp\OutputStyle;

readonly class CompilerBench
{
    private string $scss;

    /**
     * @throws RandomException
     */
    public function __construct()
    {
        $this->scss = ScssGenerator::generate(200, 4);
    }

    public function benchDartSassCompiler(): void
    {
        $compiler = new SassCompiler([
            'sourceMap'      => true,
            'includeSources' => true,
            'style'          => 'expanded',
            'sourceFile'     => 'benchmark.scss',
        ]);
        $compiler->compileString($this->scss);
    }

    public function benchSassEmbeddedPhp(): void
    {
        $compiler = new EmbeddedCompiler();
        $compiler->setOptions([
            'sourceMap'      => true,
            'sourceFile'     => 'benchmark.scss',
            'sourceMapPath'  => 'benchmark.css.map',
            'minimize'       => false,
            'includeSources' => true,
            'streamResult'   => true,
        ]);
        $compiler->compileString($this->scss);
    }

    /**
     * @throws SassException
     */
    public function benchScssPhp(): void
    {
        $compiler = new ScssCompiler();
        $compiler->setOutputStyle(OutputStyle::EXPANDED);
        $compiler->setSourceMap(ScssCompiler::SOURCE_MAP_FILE);
        $compiler->setSourceMapOptions([
            'sourceMapFilename' => 'benchmark.scss',
            'sourceMapURL'      => 'benchmark.css.map',
            'outputSourceFiles' => true,
        ]);
        $compiler->compileString($this->scss, 'benchmark.scss');
    }

    public function benchDartSassCompilerMinified(): void
    {
        $compiler = new SassCompiler([
            'sourceMap' => false,
            'style'     => 'compressed',
        ]);
        $compiler->compileString($this->scss);
    }

    public function benchSassEmbeddedPhpMinified(): void
    {
        $compiler = new EmbeddedCompiler();
        $compiler->setOptions([
            'sourceMap'    => false,
            'minimize'     => true,
            'streamResult' => true,
        ]);
        $compiler->compileString($this->scss);
    }

    /**
     * @throws SassException
     */
    public function benchScssPhpMinified(): void
    {
        $compiler = new ScssCompiler();
        $compiler->setOutputStyle(OutputStyle::COMPRESSED);
        $compiler->setSourceMap(ScssCompiler::SOURCE_MAP_NONE);
        $compiler->compileString($this->scss, 'benchmark.scss');
    }
}
