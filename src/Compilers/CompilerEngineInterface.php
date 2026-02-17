<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use DartSass\Compilers\Nodes\NodeCompiler;
use DartSass\Evaluators\InterpolationEvaluator;
use DartSass\Handlers\ExtendHandler;
use DartSass\Handlers\FunctionHandler;
use DartSass\Handlers\MixinHandler;
use DartSass\Handlers\ModuleHandler;
use DartSass\Handlers\NestingHandler;
use DartSass\Handlers\VariableHandler;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\Syntax;
use DartSass\Utils\PositionTracker;
use DartSass\Utils\ResultFormatterInterface;

interface CompilerEngineInterface
{
    public function compileString(string $string, ?Syntax $syntax = null): string;

    public function compileFile(string $filePath): string;

    public function evaluateExpression(mixed $expr): mixed;

    public function addFunction(string $name, callable $callback): void;

    public function getOptions(): array;

    public function getMappings(): array;

    public function findNodeCompiler(NodeType $nodeType): ?NodeCompiler;

    public function compileAst(array $ast, string $parentSelector = '', int $nestingLevel = 0): string;

    public function compileDeclarations(array $declarations, string $parentSelector = '', int $nestingLevel = 0): string;

    public function formatRule(string $content, string $selector, int $nestingLevel): string;

    public function getResultFormatter(): ResultFormatterInterface;

    public function getVariableHandler(): VariableHandler;

    public function getMixinHandler(): MixinHandler;

    public function getNestingHandler(): NestingHandler;

    public function getExtendHandler(): ExtendHandler;

    public function getModuleHandler(): ModuleHandler;

    public function getFunctionHandler(): FunctionHandler;

    public function getInterpolationEvaluator(): InterpolationEvaluator;

    public function getPositionTracker(): PositionTracker;

    public function getEnvironment(): Environment;

    public function getModuleCompiler(): ModuleCompiler;

    public function addMapping(array $mapping): void;
}
