<?php

declare(strict_types=1);

use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\Builtins\ModuleHandlerInterface;
use DartSass\Handlers\Builtins\QuotedStringArgumentsInterface;
use DartSass\Handlers\FunctionRouter;
use DartSass\Handlers\ModuleRegistry;
use DartSass\Utils\ResultFormatterInterface;

describe('FunctionRouter', function () {
    beforeEach(function () {
        $this->registry        = mock(ModuleRegistry::class);
        $this->resultFormatter = mock(ResultFormatterInterface::class);
        $this->router          = new FunctionRouter($this->registry, $this->resultFormatter);
    });

    describe('route method', function () {
        it('throws CompilationException when handler throws regular Exception', function () {
            $handler = mock(ModuleHandlerInterface::class);
            $handler->shouldReceive('handle')->andThrow(new Exception('Test error'));

            $this->registry->shouldReceive('getHandler')->andReturn($handler);
            $this->resultFormatter->shouldReceive('format')->never();

            expect(fn() => $this->router->route('testFunction', []))
                ->toThrow(
                    CompilationException::class,
                    'Error processing function testFunction: Test error'
                );
        });

        it('throws CompilationException for namespaced function in known SassModule namespace', function () {
            $this->registry->shouldReceive('getHandler')->with('color.testFunction')->andReturn(null);
            $this->registry->shouldReceive('getHandler')->with('testFunction')->never();

            expect(fn() => $this->router->route('color.testFunction', []))
                ->toThrow(
                    CompilationException::class,
                    'Function testFunction is not available in namespace color'
                );
        });

        it('tries global handler for namespaced function in unknown namespace', function () {
            $handler = mock(ModuleHandlerInterface::class);
            $handler->shouldReceive('handle')->andReturn('result');
            $this->resultFormatter->shouldReceive('format')->andReturn('formatted');

            $this->registry->shouldReceive('getHandler')->with('unknown.testFunction')->andReturn(null);
            $this->registry->shouldReceive('getHandler')->with('testFunction')->andReturn($handler);

            $result = $this->router->route('unknown.testFunction', []);

            expect($result)->toBe('formatted');
        });
    });

    describe('shouldPreserveQuotedStringArguments method', function () {
        it('returns true when handler supports quoted string arguments', function () {
            $handler = mock(QuotedStringArgumentsInterface::class);
            $handler->shouldReceive('shouldPreserveQuotedStringArguments')
                ->with('quote')
                ->andReturn(true);

            $this->registry->shouldReceive('getHandler')->with('quote')->andReturn($handler);

            expect($this->router->shouldPreserveQuotedStringArguments('quote'))->toBeTrue();
        });

        it('returns false when handler does not support quoted string arguments', function () {
            $handler = mock(ModuleHandlerInterface::class);

            $this->registry->shouldReceive('getHandler')->with('rgb')->andReturn($handler);

            expect($this->router->shouldPreserveQuotedStringArguments('rgb'))->toBeFalse();
        });
    });
});
