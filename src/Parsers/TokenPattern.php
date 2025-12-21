<?php

declare(strict_types=1);

namespace DartSass\Parsers;

use function array_column;
use function implode;

enum TokenPattern: string
{
    case SELECTOR                  = '(?P<selector>
        (?:
            # div, .class, #id, *
            (?:[.#]?[a-zA-Z0-9_-]+|\*)
            # :hover, ::before, :nth-child(2n)
            (?:[:]{1,2}[\w-]+(?:\([^)]*\))?)*
        )
        (?:
            # > , + , ~ , ,
            \s*[>+~,]\s*
            # div, .class, #id, *
            (?:[.#]?[a-zA-Z0-9_-]+|\*)
            # :hover, ::before
            (?:[:]{1,2}[\w-]+(?:\([^)]*\))?)*
        )*
    )';
    case HEX_COLOR                 = '(?P<hex_color>#[0-9a-fA-F]{3,8})';
    case WHITESPACE                = '(?P<whitespace>\s+)';
    case STRING                    = '(?P<string>"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"|\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\')';
    case COMMENT                   = '(?P<comment>\/\/.*?$|\/\*.*?\*\/)';
    case NUMBER                    = '(?P<number>-?(\d*\.\d+|\d+(\.\d+)?)(?:%|em|px|rem|vw|vh|s|ms|deg|fr)?)';
    case VARIABLE                  = '(?P<variable>\$[a-zA-Z_-][a-zA-Z0-9_-]*)';
    case AT_RULE                   = '(?P<at_rule>@[a-zA-Z_-]+)';
    case FUNCTION                  = '(?P<function>[a-zA-Z_-][a-zA-Z0-9_.-]*(?=\())';
    case ASTERISK                  = '(?P<asterisk>\*)';
    case LOGICAL_OPERATOR          = '(?P<logical_operator>\band\b|\bor\b)';
    case CSS_CUSTOM_PROPERTY       = '(?P<css_custom_property>--[a-zA-Z0-9_-]+)';
    case IDENTIFIER                = '(?P<identifier>-?[a-zA-Z_][a-zA-Z0-9_-]*)';
    case DOUBLE_HASH_INTERPOLATION = '(?P<double_hash_interpolation>##\{)';
    case INTERPOLATION_OPEN        = '(?P<interpolation_open>#\{)';
    case ATTRIBUTE_SELECTOR        = '(?P<attribute_selector>\[[^\]]+\])';
    case OPERATOR                  = '(?P<operator>[+\-*\/%=<>!&|.,\]#])';
    case BRACE_OPEN                = '(?P<brace_open>\{)';
    case BRACE_CLOSE               = '(?P<brace_close>\})';
    case PAREN_OPEN                = '(?P<paren_open>\()';
    case PAREN_CLOSE               = '(?P<paren_close>\))';
    case SEMICOLON                 = '(?P<semicolon>;)';
    case COLON                     = '(?P<colon>:)';

    public static function getPatterns(): array
    {
        return array_column(self::cases(), 'value', 'name');
    }

    public static function buildRegexFromPatterns(): string
    {
        $patterns = self::getPatterns();

        return '/(' . implode('|', $patterns) . ')/ms';
    }
}
