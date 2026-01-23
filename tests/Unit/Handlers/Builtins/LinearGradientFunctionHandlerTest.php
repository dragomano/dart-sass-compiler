<?php

declare(strict_types=1);

use DartSass\Handlers\Builtins\LinearGradientFunctionHandler;
use DartSass\Handlers\SassModule;
use DartSass\Utils\ResultFormatterInterface;
use Tests\ReflectionAccessor;

describe('LinearGradientFunctionHandler', function () {
    beforeEach(function () {
        $this->resultFormatter = mock(ResultFormatterInterface::class);
        $this->handler         = new LinearGradientFunctionHandler($this->resultFormatter);
        $this->accessor        = new ReflectionAccessor($this->handler);
    });

    describe('handle method', function () {
        it('handles single argument', function () {
            $this->resultFormatter->shouldReceive('format')->with('red')->andReturn('red');

            $result = $this->handler->handle('linear-gradient', ['red']);
            expect($result)->toBe('linear-gradient(red)');
        });

        it('handles multiple arguments without reconstruction', function () {
            $this->resultFormatter->shouldReceive('format')->with('red')->andReturn('red');
            $this->resultFormatter->shouldReceive('format')->with('blue')->andReturn('blue');

            $result = $this->handler->handle('linear-gradient', ['red', 'blue']);
            expect($result)->toBe('linear-gradient(red, blue)');
        });

        it('handles multiple arguments with reconstruction', function () {
            $this->resultFormatter->shouldReceive('format')->with('to')->andReturn('to');
            $this->resultFormatter->shouldReceive('format')->with('top')->andReturn('top');
            $this->resultFormatter->shouldReceive('format')->with('red')->andReturn('red');
            $this->resultFormatter->shouldReceive('format')->with('blue')->andReturn('blue');

            $result = $this->handler->handle('linear-gradient', ['to', 'top', 'red', 'blue']);
            expect($result)->toBe('linear-gradient(to top, red, blue)');
        });
    });

    describe('getModuleNamespace method', function () {
        it('returns CSS namespace', function () {
            expect($this->handler->getModuleNamespace())->toEqual(SassModule::CSS);
        });
    });

    describe('reconstructArguments method', function () {
        it('reconstructs direction with to keyword', function () {
            $result = $this->accessor->callMethod('reconstructArguments', [['to', 'top', 'red']]);
            expect($result)->toBe(['to top', 'red']);
        });

        it('reconstructs direction with to and two keywords', function () {
            $result = $this->accessor->callMethod('reconstructArguments', [['to', 'top', 'left', 'red']]);
            expect($result)->toBe(['to top left', 'red']);
        });

        it('reconstructs number unit pair', function () {
            $result = $this->accessor->callMethod('reconstructArguments', [['45', 'deg', 'red']]);
            expect($result)->toBe(['45deg', 'red']);
        });

        it('reconstructs color stop pair', function () {
            $result = $this->accessor->callMethod('reconstructArguments', [['red', '50%', 'blue']]);
            expect($result)->toBe(['red 50%', 'blue']);
        });

        it('handles single arguments', function () {
            $result = $this->accessor->callMethod('reconstructArguments', [['red']]);
            expect($result)->toBe(['red']);
        });
    });

    describe('isDirectionKeyword method', function () {
        it('returns true for valid direction keywords', function () {
            expect($this->accessor->callMethod('isDirectionKeyword', ['top']))->toBeTrue()
                ->and($this->accessor->callMethod('isDirectionKeyword', ['bottom']))->toBeTrue()
                ->and($this->accessor->callMethod('isDirectionKeyword', ['left']))->toBeTrue()
                ->and($this->accessor->callMethod('isDirectionKeyword', ['right']))->toBeTrue()
                ->and($this->accessor->callMethod('isDirectionKeyword', ['center']))->toBeTrue();
        });

        it('returns false for invalid direction keywords', function () {
            expect($this->accessor->callMethod('isDirectionKeyword', ['middle']))->toBeFalse()
                ->and($this->accessor->callMethod('isDirectionKeyword', ['123']))->toBeFalse();
        });
    });

    describe('isValidNumberUnitPair method', function () {
        it('returns true for valid number unit pairs', function () {
            expect($this->accessor->callMethod('isValidNumberUnitPair', ['45', 'deg']))->toBeTrue()
                ->and($this->accessor->callMethod('isValidNumberUnitPair', ['1.5', 'rad']))->toBeTrue()
                ->and($this->accessor->callMethod('isValidNumberUnitPair', ['-90', 'grad']))->toBeTrue()
                ->and($this->accessor->callMethod('isValidNumberUnitPair', ['0.5', 'turn']))->toBeTrue();
        });

        it('returns false for invalid number unit pairs', function () {
            expect($this->accessor->callMethod('isValidNumberUnitPair', ['abc', 'deg']))->toBeFalse()
                ->and($this->accessor->callMethod('isValidNumberUnitPair', ['45', 'px']))->toBeFalse()
                ->and($this->accessor->callMethod('isValidNumberUnitPair', ['45', 'invalid']))->toBeFalse();
        });
    });

    describe('isValidColorStopPair method', function () {
        it('returns true for valid color stop pairs', function () {
            expect($this->accessor->callMethod('isValidColorStopPair', ['red', '50%']))->toBeTrue()
                ->and($this->accessor->callMethod('isValidColorStopPair', ['#ff0000', '100px']))->toBeTrue()
                ->and($this->accessor->callMethod('isValidColorStopPair', ['rgb(255,0,0)', '0']))->toBeTrue();
        });

        it('returns false for invalid color stop pairs', function () {
            expect($this->accessor->callMethod('isValidColorStopPair', ['red', 'invalid']))->toBeFalse()
                ->and($this->accessor->callMethod('isValidColorStopPair', ['#invalid', '50%']))->toBeFalse();
        });
    });

    describe('isColorLike method', function () {
        it('returns true for color-like values', function () {
            expect($this->accessor->callMethod('isColorLike', ['red']))->toBeTrue()
                ->and($this->accessor->callMethod('isColorLike', ['#ff0000']))->toBeTrue()
                ->and($this->accessor->callMethod('isColorLike', ['#f00']))->toBeTrue()
                ->and($this->accessor->callMethod('isColorLike', ['rgb(255,0,0)']))->toBeTrue()
                ->and($this->accessor->callMethod('isColorLike', ['hsl(0,100%,50%)']))->toBeTrue();
        });

        it('returns false for non-color-like values', function () {
            expect($this->accessor->callMethod('isColorLike', ['123invalid']))->toBeFalse()
                ->and($this->accessor->callMethod('isColorLike', ['123']))->toBeFalse();
        });
    });

    describe('isPositionLike method', function () {
        it('returns true for position-like values', function () {
            expect($this->accessor->callMethod('isPositionLike', ['0']))->toBeTrue()
                ->and($this->accessor->callMethod('isPositionLike', ['50%']))->toBeTrue()
                ->and($this->accessor->callMethod('isPositionLike', ['100px']))->toBeTrue()
                ->and($this->accessor->callMethod('isPositionLike', ['-50px']))->toBeTrue()
                ->and($this->accessor->callMethod('isPositionLike', ['1.5em']))->toBeTrue();
        });

        it('returns false for non-position-like values', function () {
            expect($this->accessor->callMethod('isPositionLike', ['red']))->toBeFalse()
                ->and($this->accessor->callMethod('isPositionLike', ['invalid']))->toBeFalse();
        });
    });
})->covers(LinearGradientFunctionHandler::class);
