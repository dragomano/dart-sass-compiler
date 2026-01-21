<?php

declare(strict_types=1);

namespace DartSass\Utils;

use function array_multisort;
use function is_string;
use function json_encode;

use const PHP_INT_MAX;

class SourceMapGenerator
{
    private const VLQ_BASE_SHIFT       = 5;

    private const VLQ_BASE_MASK        = 31;

    private const VLQ_CONTINUATION_BIT = 32;

    private const BASE64_MAP = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
        'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f',
        'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v',
        'w', 'x', 'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '+', '/',
    ];

    public function generate(array $mappings, string $sourceFile, string $outputFile, array $options = []): string
    {
        $sourceMap = [
            'version'    => 3,
            'sourceRoot' => $options['sourceMapRoot'] ?? '',
            'sources'    => $options['sources'] ?? [$sourceFile],
            'names'      => [],
            'mappings'   => $this->generateMappings($mappings, $options['outputLines'] ?? null),
            'file'       => $outputFile,
        ];

        if ($options['includeSources'] ?? false) {
            $sourceMap['sourcesContent'] = [
                is_string($options['sourceContent'] ?? '') ? $options['sourceContent'] : '',
            ];
        }

        return json_encode($sourceMap, JSON_UNESCAPED_SLASHES);
    }

    private function generateMappings(array $mappings, ?int $totalLines = null): string
    {
        $genLines   = [];
        $genColumns = [];

        foreach ($mappings as $key => $mapping) {
            $genLines[$key]   = $mapping['generated']['line'] ?? 0;
            $genColumns[$key] = $mapping['generated']['column'] ?? 0;
        }

        array_multisort($genLines, SORT_ASC, $genColumns, SORT_ASC, $mappings);

        $result        = '';
        $lineSegments  = '';
        $lastGenLine   = 0;
        $lastGenCol    = 0;
        $lastOrigLine  = 0; // 0-based indexing
        $lastOrigCol   = 0;
        $lastSourceIdx = 0;

        foreach ($mappings as $mapping) {
            $gen       = $mapping['generated'];
            $orig      = $mapping['original'];
            $genLine   = $gen['line'];
            $genCol    = $gen['column'];
            $origLine  = $orig['line'];
            $origCol   = $orig['column'];
            $sourceIdx = $mapping['sourceIndex'] ?? 0;

            // Add empty segments for lines between lastGenLine + 1 and genLine - 1
            while ($lastGenLine < $genLine) {
                if ($lineSegments !== '') {
                    $result       .= $lineSegments . ';';
                    $lineSegments  = '';
                } else {
                    $result .= ';';
                }

                $lastGenLine++;
                $lastGenCol = 0;
            }

            if ($lineSegments !== '') {
                $lineSegments .= ',';
            }

            $lineSegments .= $this->encodeVLQ($genCol - $lastGenCol)
                . $this->encodeVLQ($sourceIdx - $lastSourceIdx)
                . $this->encodeVLQ($origLine - $lastOrigLine)
                . $this->encodeVLQ($origCol - $lastOrigCol);

            $lastGenCol    = $genCol;
            $lastOrigLine  = $origLine;
            $lastOrigCol   = $origCol;
            $lastSourceIdx = $sourceIdx;
        }

        if ($lineSegments !== '') {
            $result .= $lineSegments;
        }

        // Add empty segments for remaining lines up to totalLines
        if ($totalLines !== null) {
            while ($lastGenLine < $totalLines - 1) {
                $result .= ';';
                $lastGenLine++;
            }
        }

        return $result;
    }

    private function encodeVLQ(int $value): string
    {
        $encoded = '';

        $vlq = $value < 0 ? ((-$value) << 1) + 1 : $value << 1;

        do {
            $digit = $vlq & self::VLQ_BASE_MASK;
            $vlq   = (($vlq >> 1) & PHP_INT_MAX) >> (self::VLQ_BASE_SHIFT - 1);

            if ($vlq > 0) {
                $digit |= self::VLQ_CONTINUATION_BIT;
            }

            $encoded .= self::BASE64_MAP[$digit];
        } while ($vlq > 0);

        return $encoded;
    }
}
