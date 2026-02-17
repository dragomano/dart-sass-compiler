<?php

declare(strict_types=1);

use DartSass\Compilers\Strategies\AtRootStrategy;
use DartSass\Parsers\Nodes\AtRootNode;

describe('AtRootStrategy', function () {
    beforeEach(function () {
        $this->strategy = new AtRootStrategy();
    });

    it('throws InvalidArgumentException when required parameters are missing', function () {
        $node = new AtRootNode(null, null, [
            'declarations' => [],
            'nested'       => [],
        ], 1);

        expect(fn() => $this->strategy->compile($node, '', 0))
            ->toThrow(
                InvalidArgumentException::class,
                'Missing required parameters for at-root compilation'
            );
    });

    it('compiles declarations with filtered selector', function () {
        $node = new AtRootNode(null, 'rule', [
            'declarations' => [['color', 'red', 1]],
            'nested'       => [],
        ], 1);

        $compileDeclarations = fn($declarations, $selector, $level) => implode('', array_map(
            fn($decl) => "    color: red;\n",
            $declarations
        ));

        $compileAst = fn($nested, $selector, $level) => '';

        $result = $this->strategy->compile(
            $node,
            '.parent @media screen',
            0,
            null,
            $compileDeclarations,
            $compileAst
        );

        expect($result)->toBe(".parent {\n    color: red;\n}\n");
    });

    it('compiles declarations without selector when filtered selector is empty', function () {
        $node = new AtRootNode(null, null, [
            'declarations' => [['color', 'red', 1]],
            'nested'       => [],
        ], 1);

        $compileDeclarations = fn($declarations, $selector, $level) => "  color: red;\n";

        $compileAst = fn($nested, $selector, $level) => '';

        $result = $this->strategy->compile(
            $node,
            '',
            0,
            null,
            $compileDeclarations,
            $compileAst
        );

        expect($result)->toBe("  color: red;\n");
    });

    it('removes media context without clause', function () {
        $node = new AtRootNode('media', null, [
            'declarations' => [['color', 'red', 1]],
            'nested'       => [],
        ], 1);

        $compileDeclarations = fn($declarations, $selector, $level) => implode('', array_map(
            fn($decl) => "    color: red;\n",
            $declarations
        ));

        $compileAst = fn($nested, $selector, $level) => '';

        $result = $this->strategy->compile(
            $node,
            '.parent @media screen',
            0,
            null,
            $compileDeclarations,
            $compileAst
        );

        expect($result)->toBe(".parent {\n    color: red;\n}\n");
    });

    it('skips empty selector groups', function () {
        $node = new AtRootNode(null, 'rule', [
            'declarations' => [['color', 'red', 1]],
            'nested'       => [],
        ], 1);

        $compileDeclarations = fn($declarations, $selector, $level) => implode('', array_map(
            fn($decl) => "    color: red;\n",
            $declarations
        ));

        $compileAst = fn($nested, $selector, $level) => '';

        $result = $this->strategy->compile(
            $node,
            '.parent,, .child',
            0,
            null,
            $compileDeclarations,
            $compileAst
        );

        expect($result)->toBe(".parent, .child {\n    color: red;\n}\n");
    });

    it('handles selectors with parentheses', function () {
        $node = new AtRootNode(null, 'rule', [
            'declarations' => [['color', 'red', 1]],
            'nested'       => [],
        ], 1);

        $compileDeclarations = fn($declarations, $selector, $level) => implode('', array_map(
            fn($decl) => "    color: red;\n",
            $declarations
        ));

        $compileAst = fn($nested, $selector, $level) => '';

        $result = $this->strategy->compile(
            $node,
            '.parent:not(.hidden) @media (min-width: 768px)',
            0,
            null,
            $compileDeclarations,
            $compileAst
        );

        expect($result)->toBe(".parent:not(.hidden) {\n    color: red;\n}\n");
    });

    it('handles media query with multiple conditions', function () {
        $node = new AtRootNode('media', null, [
            'declarations' => [['color', 'red', 1]],
            'nested'       => [],
        ], 1);

        $compileDeclarations = fn($declarations, $selector, $level) => implode('', array_map(
            fn($decl) => "    color: red;\n",
            $declarations
        ));

        $compileAst = fn($nested, $selector, $level) => '';

        $result = $this->strategy->compile(
            $node,
            '.parent @media screen and (min-width: 768px)',
            0,
            null,
            $compileDeclarations,
            $compileAst
        );

        expect($result)->toBe(".parent {\n    color: red;\n}\n");
    });

    it('handles media query followed by selector', function () {
        $node = new AtRootNode('media', null, [
            'declarations' => [['color', 'red', 1]],
            'nested'       => [],
        ], 1);

        $compileDeclarations = fn($declarations, $selector, $level) => implode('', array_map(
            fn($decl) => "    color: red;\n",
            $declarations
        ));

        $compileAst = fn($nested, $selector, $level) => '';

        $result = $this->strategy->compile(
            $node,
            '.parent @media screen .child',
            0,
            null,
            $compileDeclarations,
            $compileAst
        );

        expect($result)->toBe(".parent .child {\n    color: red;\n}\n");
    });
});
