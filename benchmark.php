<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use Bugo\BenchmarkUtils\ScssGenerator;
use Bugo\BenchmarkUtils\BenchmarkRunner;
use Bugo\Sass\Compiler as EmbeddedCompiler;
use DartSass\Compiler as SassCompiler;
use ScssPhp\ScssPhp\Compiler as ScssCompiler;
use ScssPhp\ScssPhp\OutputStyle;

$scss = ScssGenerator::generate(200, 4);
file_put_contents('generated.scss', $scss, LOCK_EX);

echo "Generated SCSS saved to generated.scss\n";
echo 'SCSS size: ' . strlen($scss) . " bytes\n";

$minimize  = false;
$sourceMap = false;

$results = (new BenchmarkRunner())
    ->setScssCode($scss)
    ->setRuns(10)
    ->setWarmupRuns(2)
    ->setOutputDir(__DIR__)
    ->addCompiler('bugo/dart-sass-compiler', function() use ($sourceMap, $minimize) {
        return new SassCompiler([
            'sourceMap'      => $sourceMap,
            'includeSources' => true,
            'style'          => $minimize ? 'compressed' : 'expanded',
            'sourceFile'     => 'generated.scss',
            'sourceMapFile'  => 'result-dart-sass-compiler.css.map',
            'outputFile'     => 'result-dart-sass-compiler.css',
        ]);
    })
    ->addCompiler('bugo/sass-embedded-php', function() use ($sourceMap, $minimize) {
        $compiler = new EmbeddedCompiler();
        $compiler->setOptions([
            'sourceMap'      => $sourceMap,
            'sourceFile'     => 'generated.scss',
            'sourceMapPath'  => 'result-sass-embedded-php.css.map',
            'minimize'       => $minimize,
            'includeSources' => true,
            'streamResult'   => true,
        ]);
        return $compiler;
    })
    ->addCompiler('scssphp/scssphp', function() use ($sourceMap, $minimize) {
        $compiler = new ScssCompiler();
        $compiler->setOutputStyle($minimize ? OutputStyle::COMPRESSED : OutputStyle::EXPANDED);
        $compiler->setSourceMap($sourceMap ? ScssCompiler::SOURCE_MAP_FILE : ScssCompiler::SOURCE_MAP_NONE);
        $compiler->setSourceMapOptions([
            'sourceMapFilename' => 'generated.scss',
            'sourceMapURL'      => 'result-scssphp-scssphp.css.map',
            'outputSourceFiles' => true,
        ]);
        return $compiler;
    })
    ->run();

echo PHP_EOL . '## Results' . PHP_EOL;
echo BenchmarkRunner::formatTable($results);

BenchmarkRunner::updateMarkdownFile('benchmark.md', $results);
