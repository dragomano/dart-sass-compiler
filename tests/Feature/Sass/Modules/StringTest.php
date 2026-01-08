<?php

declare(strict_types=1);

use DartSass\Compiler;
use DartSass\Exceptions\CompilationException;

beforeEach(function () {
    $this->compiler = new Compiler();
});

describe('sass:string', function () {
    describe('supports string.quote function', function () {
        it('quotes unquoted strings', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                content: string.quote(hello);
                special: string.quote(hello\nworld);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              content: "hello";
              special: "hellonworld";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('leaves already quoted strings unchanged', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                double-quoted: string.quote("world");
                single-quoted: string.quote('test');
                mixed: string.quote('mixed"quote');
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              double-quoted: "world";
              single-quoted: "test";
              mixed: 'mixed"quote';
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles empty strings', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                empty: string.quote("");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              empty: "";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles strings with special characters', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                escapes: string.quote(line1\tline2);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              escapes: "line1tline2";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('throws exception when argument is not a string', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                content: string.quote(123);
            }
            SCSS;

            expect(fn() => $this->compiler->compileString($scss))
                ->toThrow(CompilationException::class, 'quote() argument must be a string');
        });
    });

    describe('supports string.index function', function () {
        it('finds substring at the beginning', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                position: string.index("hello", "h");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              position: 1;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('finds substring in the middle', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                position: string.index("hello", "ll");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              position: 3;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('finds substring at the end', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                position: string.index("hello", "o");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              position: 5;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('returns null when substring not found', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                position: string.index("hello", "x");
            }
            SCSS;

            $expected = '';

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('finds first occurrence with multiple matches', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                position: string.index("hello", "l");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              position: 3;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles empty strings', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                empty-string: string.index("", "a");
                empty-substring: string.index("hello", "");
                both-empty: string.index("", "");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              empty-substring: 1;
              both-empty: 1;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles case-sensitive matching', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                lowercase: string.index("Hello", "h");
                uppercase: string.index("hello", "H");
            }
            SCSS;

            $expected = '';

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('throws exception when first argument is not a string', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                position: string.index(123, "sub");
            }
            SCSS;

            expect(fn() => $this->compiler->compileString($scss))
                ->toThrow(CompilationException::class, 'index() first argument must be a string');
        });

        it('throws exception when second argument is not a string', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                position: string.index("string", 123);
            }
            SCSS;

            expect(fn() => $this->compiler->compileString($scss))
                ->toThrow(CompilationException::class, 'index() second argument must be a string');
        });
    });

    describe('supports string.length function', function () {
        it('returns length of unquoted string', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                length: string.length(hello);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              length: 5;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('returns length of quoted string', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                length: string.length("hello world");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              length: 11;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('returns length of empty string', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                length: string.length("");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              length: 0;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('throws exception when argument is not a string', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                length: string.length(123);
            }
            SCSS;

            expect(fn() => $this->compiler->compileString($scss))
                ->toThrow(CompilationException::class, 'length() argument must be a string');
        });
    });

    describe('supports string.insert function', function () {
        it('inserts substring at the beginning', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.insert("world", "hello ", 1);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "hello world";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('inserts substring in the middle', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.insert("heo", "ll", 3);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "hello";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('inserts substring at the end', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.insert("hello", " world", 6);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "hello world";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles index less than 1', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.insert("world", "hello ", 0);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "hello world";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles index greater than string length', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.insert("hello", " world", 10);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "hello world";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('throws exception when first argument is not a string', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.insert(123, "insert", 1);
            }
            SCSS;

            expect(fn() => $this->compiler->compileString($scss))
                ->toThrow(CompilationException::class, 'insert() first argument must be a string');
        });

        it('throws exception when second argument is not a string', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.insert("string", 123, 1);
            }
            SCSS;

            expect(fn() => $this->compiler->compileString($scss))
                ->toThrow(CompilationException::class, 'insert() second argument must be a string');
        });

        it('throws exception when third argument is not a number', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.insert("string", "insert", "notnumber");
            }
            SCSS;

            expect(fn() => $this->compiler->compileString($scss))
                ->toThrow(CompilationException::class, 'insert() third argument must be a number');
        });
    });

    describe('supports string.slice function', function () {
        it('slices string from start', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.slice("hello world", 1);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "hello world";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('slices string with start and end', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.slice("hello world", 1, 6);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "hello ";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('slices string with negative start', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.slice("hello world", -5);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "world";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('slices string with negative end', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.slice("hello world", 1, -6);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "hello";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('throws exception when first argument is not a string', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.slice(123, 1);
            }
            SCSS;

            expect(fn() => $this->compiler->compileString($scss))
                ->toThrow(CompilationException::class, 'slice() first argument must be a string');
        });

        it('throws exception when second argument is not a number', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.slice("string", "notnumber");
            }
            SCSS;

            expect(fn() => $this->compiler->compileString($scss))
                ->toThrow(CompilationException::class, 'slice() second argument must be a number');
        });
    });

    describe('supports string.split function', function () {
        it('splits string by separator', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.split("hello,world", ",");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "hello", "world";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('splits string with limit', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.split("a,b,c", ",", 2);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "a", "b, c";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('splits string into characters when separator is empty', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.split("abc", "");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "a", "b", "c";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('throws exception when first argument is not a string', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.split(123, ",");
            }
            SCSS;

            expect(fn() => $this->compiler->compileString($scss))
                ->toThrow(CompilationException::class, 'split() first argument must be a string');
        });

        it('throws exception when second argument is not a string', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.split("string", 123);
            }
            SCSS;

            expect(fn() => $this->compiler->compileString($scss))
                ->toThrow(CompilationException::class, 'split() second argument must be a string');
        });
    });

    describe('supports string.to-upper-case function', function () {
        it('converts string to uppercase', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.to-upper-case("hello world");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "HELLO WORLD";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles empty string', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.to-upper-case("");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles unicode characters', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.to-upper-case("héllo wörld");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            @charset "UTF-8";
            .test {
              result: "HéLLO WöRLD";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles quoted strings', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.to-upper-case("hello");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "HELLO";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles unquoted strings', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.to-upper-case(hello);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "HELLO";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('throws exception when argument is not a string', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.to-upper-case(123);
            }
            SCSS;

            expect(fn() => $this->compiler->compileString($scss))
                ->toThrow(CompilationException::class, 'toUpperCase() argument must be a string');
        });
    });

    describe('supports string.to-lower-case function', function () {
        it('converts string to lowercase', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.to-lower-case("HELLO WORLD");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "hello world";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles empty string', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.to-lower-case("");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles unicode characters', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.to-lower-case("HÉLLO WÖRLD");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            @charset "UTF-8";
            .test {
              result: "hÉllo wÖrld";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles quoted strings', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.to-lower-case("HELLO");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "hello";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles unquoted strings', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.to-lower-case(HELLO);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "hello";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('throws exception when argument is not a string', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                result: string.to-lower-case(123);
            }
            SCSS;

            expect(fn() => $this->compiler->compileString($scss))
                ->toThrow(CompilationException::class, 'toLowerCase() argument must be a string');
        });
    });

    describe('supports string.unique-id function', function () {
        it('generates unique id with correct format', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                content: string.unique-id();
            }
            SCSS;

            expect($this->compiler->compileString($scss))
                ->toMatch('/content:\s*"([a-zA-Z][a-zA-Z0-9]{5,11})";/');
        });

        it('throws exception when arguments are provided', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                content: string.unique-id("arg");
            }
            SCSS;

            expect(fn() => $this->compiler->compileString($scss))
                ->toThrow(CompilationException::class);
        });

        it('generates unique ids when called multiple times', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                id1: string.unique-id();
                id2: string.unique-id();
            }
            SCSS;

            $css = $this->compiler->compileString($scss);

            // Extract ids from CSS
            preg_match('/id1:\s*"([a-zA-Z0-9]+)";/', $css, $matches1);
            preg_match('/id2:\s*"([a-zA-Z0-9]+)";/', $css, $matches2);

            expect($matches1[1])->not()->toBe($matches2[1]);
        });
    });

    describe('supports string.unquote function', function () {
        it('removes quotes from double-quoted strings', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                content: string.unquote("hello");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              content: hello;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('removes quotes from single-quoted strings', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                content: string.unquote('world');
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              content: world;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('leaves unquoted strings unchanged', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                content: string.unquote(hello);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              content: hello;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles empty strings', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                content: string.unquote("");
            }
            SCSS;

            $expected = '';

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles strings with escape sequences', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                content: string.unquote("line1\\nline2");
                tabbed: string.unquote("col1\\tcol2");
                slashed: string.unquote("path\\to\\file");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              content: line1\nline2;
              tabbed: col1\tcol2;
              slashed: path\to\file;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles strings with special characters', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                content: string.unquote("hello\nworld");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              content: hellonworld;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('throws exception when argument is not a string', function () {
            $scss = <<<'SCSS'
            @use 'sass:string';

            .test {
                content: string.unquote(123);
            }
            SCSS;

            expect(fn() => $this->compiler->compileString($scss))
                ->toThrow(CompilationException::class, 'unquote() argument must be a string');
        });
    });
});

describe('global string functions', function () {
    describe('supports global quote function', function () {
        it('quotes unquoted strings', function () {
            $scss = <<<'SCSS'
            .test {
                content: quote(hello);
                special: quote(hello\nworld);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              content: "hello";
              special: "hellonworld";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('leaves already quoted strings unchanged', function () {
            $scss = <<<'SCSS'
            .test {
                double-quoted: quote("world");
                single-quoted: quote('test');
                mixed: quote('mixed"quote');
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              double-quoted: "world";
              single-quoted: "test";
              mixed: 'mixed"quote';
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles empty strings', function () {
            $scss = <<<'SCSS'
            .test {
                empty: quote("");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              empty: "";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports global str-index function', function () {
        it('finds substring at the beginning', function () {
            $scss = <<<'SCSS'
            .test {
                position: str-index("hello", "h");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              position: 1;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('returns null when substring not found', function () {
            $scss = <<<'SCSS'
            .test {
                position: str-index("hello", "x");
            }
            SCSS;

            $expected = '';

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports global str-length function', function () {
        it('returns length of unquoted string', function () {
            $scss = <<<'SCSS'
            .test {
                length: str-length(hello);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              length: 5;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('returns length of quoted string', function () {
            $scss = <<<'SCSS'
            .test {
                length: str-length("hello world");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              length: 11;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports global str-insert function', function () {
        it('inserts substring at the beginning', function () {
            $scss = <<<'SCSS'
            .test {
                result: str-insert("world", "hello ", 1);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "hello world";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles index greater than string length', function () {
            $scss = <<<'SCSS'
            .test {
                result: str-insert("hello", " world", 10);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "hello world";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports global str-slice function', function () {
        it('slices string from start', function () {
            $scss = <<<'SCSS'
            .test {
                result: str-slice("hello world", 1);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "hello world";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('slices string with negative start', function () {
            $scss = <<<'SCSS'
            .test {
                result: str-slice("hello world", -5);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "world";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports global to-upper-case function', function () {
        it('converts string to uppercase', function () {
            $scss = <<<'SCSS'
            .test {
                result: to-upper-case("hello world");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "HELLO WORLD";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles empty string', function () {
            $scss = <<<'SCSS'
            .test {
                result: to-upper-case("");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles unicode characters', function () {
            $scss = <<<'SCSS'
            .test {
                result: to-upper-case("héllo wörld");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            @charset "UTF-8";
            .test {
              result: "HéLLO WöRLD";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles quoted strings', function () {
            $scss = <<<'SCSS'
            .test {
                result: to-upper-case("hello");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "HELLO";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles unquoted strings', function () {
            $scss = <<<'SCSS'
            .test {
                result: to-upper-case(hello);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "HELLO";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('throws exception when argument is not a string', function () {
            $scss = <<<'SCSS'
            .test {
                result: to-upper-case(123);
            }
            SCSS;

            expect(fn() => $this->compiler->compileString($scss))
                ->toThrow(CompilationException::class, 'toUpperCase() argument must be a string');
        });
    });

    describe('supports global to-lower-case function', function () {
        it('converts string to lowercase', function () {
            $scss = <<<'SCSS'
            .test {
                result: to-lower-case("HELLO WORLD");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "hello world";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles empty string', function () {
            $scss = <<<'SCSS'
            .test {
                result: to-lower-case("");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles unicode characters', function () {
            $scss = <<<'SCSS'
            .test {
                result: to-lower-case("HÉLLO WÖRLD");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            @charset "UTF-8";
            .test {
              result: "hÉllo wÖrld";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles quoted strings', function () {
            $scss = <<<'SCSS'
            .test {
                result: to-lower-case("HELLO");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "hello";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles unquoted strings', function () {
            $scss = <<<'SCSS'
            .test {
                result: to-lower-case(HELLO);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              result: "hello";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('throws exception when argument is not a string', function () {
            $scss = <<<'SCSS'
            .test {
                result: to-lower-case(123);
            }
            SCSS;

            expect(fn() => $this->compiler->compileString($scss))
                ->toThrow(CompilationException::class, 'toLowerCase() argument must be a string');
        });
    });

    describe('supports global unique-id function', function () {
        it('generates unique id with correct format', function () {
            $scss = <<<'SCSS'
            .test {
                content: unique-id();
            }
            SCSS;

            expect($this->compiler->compileString($scss))
                ->toMatch('/content:\s*"([a-zA-Z][a-zA-Z0-9]{5,11})";/');
        });

        it('throws exception when arguments are provided', function () {
            $scss = <<<'SCSS'
            .test {
                content: unique-id("arg");
            }
            SCSS;

            expect(fn() => $this->compiler->compileString($scss))
                ->toThrow(CompilationException::class);
        });

        it('generates unique ids when called multiple times', function () {
            $scss = <<<'SCSS'
            .test {
                id1: unique-id();
                id2: unique-id();
            }
            SCSS;

            $css = $this->compiler->compileString($scss);

            // Extract ids from CSS
            preg_match('/id1:\s*"([a-zA-Z0-9]+)";/', $css, $matches1);
            preg_match('/id2:\s*"([a-zA-Z0-9]+)";/', $css, $matches2);

            expect($matches1[1])->not()->toBe($matches2[1]);
        });
    });

    describe('supports global unquote function', function () {
        it('removes quotes from double-quoted strings', function () {
            $scss = <<<'SCSS'
            .test {
                content: unquote("hello");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              content: hello;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('removes quotes from single-quoted strings', function () {
            $scss = <<<'SCSS'
            .test {
                content: unquote('world');
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              content: world;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('leaves unquoted strings unchanged', function () {
            $scss = <<<'SCSS'
            .test {
                content: unquote(hello);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              content: hello;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles empty strings', function () {
            $scss = <<<'SCSS'
            .test {
                content: unquote("");
            }
            SCSS;

            $expected = '';

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles strings with escape sequences', function () {
            $scss = <<<'SCSS'
            .test {
                content: unquote("line1\\nline2");
                tabbed: unquote("col1\\tcol2");
                slashed: unquote("path\\to\\file");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              content: line1\nline2;
              tabbed: col1\tcol2;
              slashed: path\to\file;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles strings with special characters', function () {
            $scss = <<<'SCSS'
            .test {
                content: unquote("hello\nworld");
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              content: hellonworld;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('throws exception when argument is not a string', function () {
            $scss = <<<'SCSS'
            .test {
                content: unquote(123);
            }
            SCSS;

            expect(fn() => $this->compiler->compileString($scss))
                ->toThrow(CompilationException::class, 'unquote() argument must be a string');
        });
    });
});
