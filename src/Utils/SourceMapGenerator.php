<?php

declare(strict_types=1);

namespace DartSass\Utils;

use function abs;
use function implode;
use function is_string;
use function json_encode;
use function usort;

class SourceMapGenerator
{
    private const BASE64_ALPHABET = [
        'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P',
        'Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f',
        'g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v',
        'w','x','y','z','0','1','2','3','4','5','6','7','8','9','+','/',
    ];

    public function generate(array $mappings, string $sourceFile, string $outputFile, array $options = []): string
    {
        $sourceMap = [
            'version'    => 3,
            'sourceRoot' => $options['sourceMapRoot'] ?? '',
            'sources'    => $options['sources'] ?? [$sourceFile],
            'names'      => [],
            'mappings'   => $this->generateMappings($mappings),
            'file'       => $outputFile,
        ];

        if ($options['includeSources'] ?? false) {
            $sourceMap['sourcesContent'] = [
                is_string($options['sourceContent'] ?? '') ? $options['sourceContent'] : ''
            ];
        }

        return json_encode($sourceMap, JSON_UNESCAPED_SLASHES);
    }

    private function generateMappings(array $mappings): string
    {
        usort(
            $mappings,
            fn($a, $b): int =>
            ($a['generated']['line'] ?? 0) <=> ($b['generated']['line'] ?? 0)
                ?: ($a['generated']['column'] ?? 0) <=> ($b['generated']['column'] ?? 0)
        );

        $lineGroups  = $currentLineMappings = [];
        $lastGenLine = $lastGenCol = $lastOrigLine = $lastOrigCol = $lastSourceIdx = 0;

        foreach ($mappings as $mapping) {
            extract([
                'generatedLine'   => $mapping['generated']['line'],
                'generatedColumn' => $mapping['generated']['column'],
                'originalLine'    => $mapping['original']['line'],
                'originalColumn'  => $mapping['original']['column'],
                'sourceIndex'     => $mapping['sourceIndex'] ?? 0,
            ]);

            if ($generatedLine !== $lastGenLine) {
                if ($currentLineMappings) {
                    $lineGroups[] = implode(',', $currentLineMappings);
                    $currentLineMappings = [];
                }

                [$lastGenCol, $lastOrigLine, $lastOrigCol, $lastSourceIdx, $lastGenLine] = [0, 0, 0, 0, $generatedLine];
            }

            $segment = implode('', [
                $this->encodeVLQ($generatedColumn - $lastGenCol),
                $this->encodeVLQ($sourceIndex - $lastSourceIdx),
                $this->encodeVLQ($originalLine - $lastOrigLine),
                $this->encodeVLQ($originalColumn - $lastOrigCol)
            ]);

            $currentLineMappings[] = $segment;
            [$lastGenCol, $lastOrigLine, $lastOrigCol, $lastSourceIdx] =
                [$generatedColumn, $originalLine, $originalColumn, $sourceIndex];
        }

        if ($currentLineMappings) {
            $lineGroups[] = implode(',', $currentLineMappings);
        }

        return implode(';', $lineGroups);
    }

    private function encodeVLQ(int $value): string
    {
        $vlq   = '';
        $sign  = $value < 0 ? 1 : 0;
        $value = abs($value) << 1 | $sign;

        do {
            $vlq .= self::BASE64_ALPHABET[$value & 0x1F];
            $value >>= 5;
        } while ($value > 0);

        return $vlq;
    }
}
