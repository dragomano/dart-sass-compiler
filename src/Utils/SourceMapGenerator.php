<?php

declare(strict_types=1);

namespace DartSass\Utils;

use InvalidArgumentException;

use function abs;
use function implode;
use function is_string;
use function json_encode;
use function usort;

class SourceMapGenerator
{
    private const BASE64_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

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
        $lineGroups          = [];
        $lastGeneratedLine   = 0;
        $lastGeneratedColumn = 0;
        $lastOriginalLine    = 0;
        $lastOriginalColumn  = 0;
        $lastSourceIndex     = 0;
        $currentLineMappings = [];

        usort($mappings, function ($a, $b) {
            $lineDiff = ($a['generated']['line'] ?? 0) - ($b['generated']['line'] ?? 0);

            return $lineDiff !== 0 ? $lineDiff : ($a['generated']['column'] ?? 0) - ($b['generated']['column'] ?? 0);
        });

        foreach ($mappings as $mapping) {
            if (! isset(
                $mapping['generated']['line'],
                $mapping['generated']['column'],
                $mapping['original']['line'],
                $mapping['original']['column']
            )) {
                throw new InvalidArgumentException('Invalid mapping format');
            }

            $generatedLine   = $mapping['generated']['line'];
            $generatedColumn = $mapping['generated']['column'];
            $originalLine    = $mapping['original']['line'];
            $originalColumn  = $mapping['original']['column'];
            $sourceIndex     = $mapping['sourceIndex'] ?? 0;
            $nameIndex       = $mapping['nameIndex'] ?? null;

            if ($generatedLine < 0 || $generatedColumn < 0 || $originalLine < 0 || $originalColumn < 0) {
                continue;
            }

            if ($generatedLine !== $lastGeneratedLine) {
                if (! empty($currentLineMappings)) {
                    $lineGroups[] = implode(',', $currentLineMappings);
                    $currentLineMappings = [];
                }

                $lastGeneratedColumn = 0;
                $lastOriginalLine    = 0;
                $lastOriginalColumn  = 0;
                $lastSourceIndex     = 0;
                $lastGeneratedLine   = $generatedLine;
            }

            $columnDiff         = $generatedColumn - $lastGeneratedColumn;
            $sourceIndexDiff    = $sourceIndex - $lastSourceIndex;
            $originalLineDiff   = $originalLine - $lastOriginalLine;
            $originalColumnDiff = $originalColumn - $lastOriginalColumn;

            $encodedSegment = $this->encodeVLQ($columnDiff) .
                $this->encodeVLQ($sourceIndexDiff) .
                $this->encodeVLQ($originalLineDiff) .
                $this->encodeVLQ($originalColumnDiff);

            if ($nameIndex !== null) {
                $encodedSegment .= $this->encodeVLQ($nameIndex);
            }

            $currentLineMappings[] = $encodedSegment;

            $lastGeneratedColumn = $generatedColumn;
            $lastOriginalLine    = $originalLine;
            $lastOriginalColumn  = $originalColumn;
            $lastSourceIndex     = $sourceIndex;
        }

        if (! empty($currentLineMappings)) {
            $lineGroups[] = implode(',', $currentLineMappings);
        }

        return implode(';', $lineGroups);
    }

    private function encodeVLQ(int $value): string
    {
        $vlq = '';
        $sign = $value < 0 ? 1 : 0;
        $value = abs($value) << 1 | $sign;

        do {
            $digit = $value & 0x1F;
            $value >>= 5;

            if ($value > 0) {
                $digit |= 0x20; // Continuation bit
            }

            $vlq .= $this->base64Encode($digit);
        } while ($value > 0);

        return $vlq;
    }

    private function base64Encode(int $value): string
    {
        if ($value < 0 || $value > 63) {
            throw new InvalidArgumentException('Value must be between 0 and 63');
        }

        return self::BASE64_ALPHABET[$value];
    }
}
