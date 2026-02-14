<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use Benchmarks\ScssGenerator;
use Bugo\Sass\Compiler as EmbeddedCompiler;
use DartSass\Exceptions\CompilationException;
use DartSass\Exceptions\SyntaxException;
use DartSass\Compiler as SassCompiler;
use Random\RandomException;
use ScssPhp\ScssPhp\Compiler as ScssCompiler;
use ScssPhp\ScssPhp\OutputStyle;

try {
    $scss = ScssGenerator::generate(200, 4);
    file_put_contents('generated.scss', $scss, LOCK_EX);

    echo "Generated SCSS saved to generated.scss\n";
    echo 'SCSS size: ' . strlen($scss) . " bytes\n";

    $minimize = false;
    $sourceMap = true;

    $compilers = [
        'bugo/dart-sass-compiler' => function () use ($sourceMap, $minimize) {
            return new SassCompiler([
                'sourceMap'      => $sourceMap,
                'includeSources' => true,
                'style'          => $minimize ? 'compressed' : 'expanded',
                'sourceFile'     => 'generated.scss',
                'sourceMapFile'  => 'result-dart-sass-compiler.css.map',
                'outputFile'     => 'result-dart-sass-compiler.css',
            ]);
        },
        'bugo/sass-embedded-php' => function () use ($sourceMap, $minimize) {
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
        },
        'scssphp/scssphp' => function () use ($sourceMap, $minimize) {
            $compiler = new ScssCompiler();
            $compiler->setOutputStyle($minimize ? OutputStyle::COMPRESSED : OutputStyle::EXPANDED);
            $compiler->setSourceMap($sourceMap ? ScssCompiler::SOURCE_MAP_FILE : ScssCompiler::SOURCE_MAP_NONE);
            $compiler->setSourceMapOptions([
                'sourceMapFilename' => 'generated.scss',
                'sourceMapURL'      => 'result-scssphp-scssphp.css.map',
                'outputSourceFiles' => true,
            ]);

            return $compiler;
        },
    ];

    $results = [];
    $runs = 10;

    foreach ($compilers as $name => $compilerFactory) {
        for ($warmup = 0; $warmup < 2; $warmup++) {
            $compiler = $compilerFactory();
            if ($name === 'scssphp/scssphp') {
                $compiler->compileString($scss, 'generated.scss');
            } else {
                $compiler->compileString($scss);
            }
        }

        $times = [];
        $css = '';
        $map = null;
        $maxMemDelta = 0;
        $package = str_replace('/', '-', $name);
        $cssMap = "result-$package.css.map";

        try {
            for ($i = 0; $i < $runs; $i++) {
                gc_collect_cycles();
                $memBefore = memory_get_usage();
                $start = hrtime(true);
                $compiler = $compilerFactory();

                if ($name === 'scssphp/scssphp') {
                    $result = $compiler->compileString($scss, 'generated.scss');
                    $css = $result->getCss();
                    $map = $result->getSourceMap();
                    if ($map && $i === 0) {
                        file_put_contents($cssMap, $map, LOCK_EX);
                    }
                } elseif ($name === 'bugo/sass-embedded-php') {
                    $css = $compiler->compileString($scss);
                    if ($i === 0 && file_exists($cssMap)) {
                        $map = file_get_contents($cssMap);
                    }
                } else {
                    $css = $compiler->compileString($scss);
                    if ($i === 0 && file_exists($cssMap)) {
                        $map = file_get_contents($cssMap);
                    }

                    if ($i === 0 && file_exists(__DIR__ . '/output.css.map')) {
                        unlink(__DIR__ . '/output.css.map');
                    }
                }

                $times[] = (hrtime(true) - $start) / 1e9;
                $memAfter = memory_get_usage();
                $maxMemDelta = max($maxMemDelta, $memAfter - $memBefore);

                unset($compiler, $result);
            }

            sort($times);
            $trim = min(2, intdiv(count($times) - 1, 2));
            for ($j = 0; $j < $trim; $j++) {
                array_shift($times);
                array_pop($times);
            }

            $time = array_sum($times) / count($times);
            $memUsed = $maxMemDelta / 1024 / 1024;

            file_put_contents("result-$package.css", $css, LOCK_EX);
            $cssSize = filesize("result-$package.css") / 1024;

            $results[$name] = ['time' => $time, 'size' => $cssSize, 'memory' => $memUsed];
        } catch (CompilationException $e) {
            echo "Compilation error in $name: " . $e->getMessage() . PHP_EOL;
            $results[$name] = ['time' => 'Compilation error', 'size' => 'N/A', 'memory' => 'N/A'];
        } catch (SyntaxException $e) {
            echo "Syntax error in $name: " . $e->getMessage() . PHP_EOL;
            $results[$name] = ['time' => 'Syntax error', 'size' => 'N/A', 'memory' => 'N/A'];
        } catch (Exception $e) {
            echo "General error in $name: " . $e->getMessage() . PHP_EOL;
            $results[$name] = ['time' => 'Error', 'size' => 'N/A', 'memory' => 'N/A'];
        }
    }

    // Output results in console
    echo PHP_EOL . '## Results' . PHP_EOL;
    echo '| Compiler | Time (sec) | CSS Size (KB) | Memory (MB) |' . PHP_EOL;
    echo '|------------|-------------|---------------|-------------|' . PHP_EOL;

    $tableData = '';
    foreach ($results as $name => $data) {
        $timeStr = is_numeric($data['time']) ? number_format($data['time'], 4) : $data['time'];
        $sizeStr = is_numeric($data['size']) ? number_format($data['size'], 2) : $data['size'];
        $memStr  = is_numeric($data['memory']) ? number_format($data['memory'], 2) : $data['memory'];
        $freshData = "| $name | $timeStr | $sizeStr | $memStr |" . PHP_EOL;
        $tableData .= $freshData;
        echo $freshData;
    }

    // Now update the table in Markdown file
    $mdContent  = file_get_contents('benchmark.md');

    // Get current OS and PHP version
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        exec('cmd /c ver', $output);
        $verOutput = implode("\n", $output);
        if (preg_match('/\[Version ([\d.]+)]/', $verOutput, $matches)) {
            $build = $matches[1];
            $buildNum = (int) explode('.', $build)[2];
            if ($buildNum >= 22000) {
                $os = 'Windows 11';
            } else {
                $os = 'Windows 10';
            }

            // Determine release version
            if ($buildNum >= 28000) {
                $release = '26H1';
            } elseif ($buildNum >= 26200) {
                $release = '25H2';
            } elseif ($buildNum >= 26100) {
                $release = '24H2';
            } elseif ($buildNum >= 22631) {
                $release = '23H2';
            } elseif ($buildNum >= 22621) {
                $release = '22H2';
            } elseif ($buildNum >= 22000) {
                $release = '21H2';
            } else {
                $release = 'Unknown';
            }

            $os .= ' ' . $release . ' (Build ' . $build . ')';
        } else {
            $os = php_uname('s') . ' ' . php_uname('r');
        }
    } else {
        $os = php_uname('s') . ' ' . php_uname('r');
    }

    $phpVersion = PHP_VERSION;

    // Replace OS and PHP version in the content
    $mdContent = preg_replace('/- \*\*OS\*\*: .+/', '- **OS**: ' . $os, $mdContent);
    $mdContent = preg_replace('/- \*\*PHP version\*\*: .+/', '- **PHP version**: ' . $phpVersion, $mdContent);

    $tableStart = strpos($mdContent, '| Compiler');
    $tableOld   = substr($mdContent, $tableStart);

    $newTable = '| Compiler | Time (sec) | CSS Size (KB) | Memory (MB) |' . PHP_EOL;
    $newTable .= '|------------|-------------|---------------|-------------|' . PHP_EOL;
    $newTable .= $tableData;

    $mdContent   = str_replace($tableOld, $newTable, $mdContent);
    $scssContent = file_get_contents('generated.scss');
    $mdContent .= "\n*Note: These results are approximate. Run `composer run benchmark` from the project root to see the actual results.*\n";

    file_put_contents('benchmark.md', $mdContent);
} catch (RandomException $e) {
    dump($e->getMessage());
}
