<?php declare(strict_types=1);

use Rector\CodingStyle\Rector\If_\NullableCompareToNullRector;
use Rector\Config\RectorConfig;
use Rector\Exception\Configuration\InvalidConfigurationException;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;

try {
    return RectorConfig::configure()
        ->withPaths([
            __DIR__ . '/benchmarks',
            __DIR__ . '/src',
        ])
        ->withSkip([
            ChangeSwitchToMatchRector::class,
            NullableCompareToNullRector::class,
            NullToStrictStringFuncCallArgRector::class,
        ])
        ->withPhpSets()
        ->withTypeCoverageLevel(10)
        ->withDeadCodeLevel(10)
        ->withCodeQualityLevel(10)
        ->withCodingStyleLevel(9);
} catch (InvalidConfigurationException $e) {
    echo $e->getMessage();
}
