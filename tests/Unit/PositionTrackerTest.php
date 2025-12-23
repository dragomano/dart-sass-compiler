<?php

declare(strict_types=1);

use DartSass\Utils\PositionTracker;

beforeEach(function () {
    $this->tracker = new PositionTracker();
});

it('initializes with empty source code', function () {
    expect($this->tracker->getLine())->toBe(1)
        ->and($this->tracker->getColumn())->toBe(0);
});

it('initializes with source code', function () {
    $sourceCode = "line1\nline2\nline3";
    $tracker = new PositionTracker($sourceCode);

    expect($tracker->getLine())->toBe(1)
        ->and($tracker->getColumn())->toBe(0);
});

it('sets source code and resets position', function () {
    $sourceCode = "test\ncode\nhere";
    $this->tracker->setSourceCode($sourceCode);

    expect($this->tracker->getLine())->toBe(1)
        ->and($this->tracker->getColumn())->toBe(0);
});

it('resets position to initial state', function () {
    $this->tracker->updatePosition('some text');
    $this->tracker->updatePosition("\nanother line");

    expect($this->tracker->getLine())->toBe(2)
        ->and($this->tracker->getColumn())->toBeGreaterThan(0);

    $this->tracker->reset();

    expect($this->tracker->getLine())->toBe(1)
        ->and($this->tracker->getColumn())->toBe(0);
});

it('updates position with single line text', function () {
    $this->tracker->updatePosition('hello');

    expect($this->tracker->getLine())->toBe(1)
        ->and($this->tracker->getColumn())->toBe(5);
});

it('updates position with multiple lines', function () {
    $this->tracker->updatePosition("line1\nline2");

    expect($this->tracker->getLine())->toBe(2)
        ->and($this->tracker->getColumn())->toBe(5);
});

it('updates position with multiple newlines', function () {
    $this->tracker->updatePosition("line1\nline2\nline3\nline4");

    expect($this->tracker->getLine())->toBe(4)
        ->and($this->tracker->getColumn())->toBe(5);
});

it('accumulates position updates', function () {
    $this->tracker->updatePosition('hello');

    expect($this->tracker->getColumn())->toBe(5);

    $this->tracker->updatePosition(' world');

    expect($this->tracker->getColumn())->toBe(11);

    $this->tracker->updatePosition("\nnext line");

    expect($this->tracker->getLine())->toBe(2)
        ->and($this->tracker->getColumn())->toBe(9);
});

it('returns current position as array', function () {
    $this->tracker->updatePosition("test\nline");

    $position = $this->tracker->getCurrentPosition();

    expect($position)->toBeArray()
        ->and($position['line'])->toBe(2)
        ->and($position['column'])->toBe(4);
});

it('gets line and column separately', function () {
    expect($this->tracker->getLine())->toBe(1)
        ->and($this->tracker->getColumn())->toBe(0);

    $this->tracker->updatePosition("line1\nline2");

    expect($this->tracker->getLine())->toBe(2)
        ->and($this->tracker->getColumn())->toBe(5);
});

it('calculates indentation for valid lines', function () {
    $sourceCode = "    indented line\n  less indented\n\t\ttab indented";
    $tracker = new PositionTracker($sourceCode);

    expect($tracker->calculateIndentation(1))->toBe(4)
        ->and($tracker->calculateIndentation(2))->toBe(2)
        ->and($tracker->calculateIndentation(3))->toBe(2);
});

it('calculates zero indentation for non-indented lines', function () {
    $sourceCode = "no indentation\nanother line";
    $tracker = new PositionTracker($sourceCode);

    expect($tracker->calculateIndentation(1))->toBe(0)
        ->and($tracker->calculateIndentation(2))->toBe(0);
});

it('calculates indentation with mixed spaces and tabs', function () {
    $sourceCode = " \t mixed indentation\n\t    different mix";
    $tracker = new PositionTracker($sourceCode);

    expect($tracker->calculateIndentation(1))->toBe(3)
        ->and($tracker->calculateIndentation(2))->toBe(5);
});

it('calculates zero indentation for empty lines', function () {
    $sourceCode = "line1\n\nline3";
    $tracker = new PositionTracker($sourceCode);

    expect($tracker->calculateIndentation(2))->toBe(0);
});

it('returns zero for out of bounds lines', function () {
    $sourceCode = "line1\nline2";
    $tracker = new PositionTracker($sourceCode);

    expect($tracker->calculateIndentation(0))->toBe(0)
        ->and($tracker->calculateIndentation(3))->toBe(0)
        ->and($tracker->calculateIndentation(100))->toBe(0);
});

it('returns zero for empty source code', function () {
    $tracker = new PositionTracker('');

    expect($tracker->calculateIndentation(1))->toBe(0);
});

it('returns zero when no source code is set', function () {
    expect($this->tracker->calculateIndentation(1))->toBe(0);
});

it('gets state as array', function () {
    $this->tracker->updatePosition("test\nline");

    $state = $this->tracker->getState();

    expect($state)->toBeArray()
        ->and($state['line'])->toBe(2)
        ->and($state['column'])->toBe(4);
});

it('sets state from array', function () {
    $state = ['line' => 5, 'column' => 10];
    $this->tracker->setState($state);

    expect($this->tracker->getLine())->toBe(5)
        ->and($this->tracker->getColumn())->toBe(10)
        ->and($this->tracker->getCurrentPosition())->toBe($state);
});

it('persists state across updates', function () {
    $this->tracker->setState(['line' => 3, 'column' => 7]);
    $this->tracker->updatePosition('more text');

    expect($this->tracker->getLine())->toBe(3)
        ->and($this->tracker->getColumn())->toBe(16);

    $this->tracker->reset();

    expect($this->tracker->getLine())->toBe(1)
        ->and($this->tracker->getColumn())->toBe(0);
});

it('handles newline-only update', function () {
    $this->tracker->updatePosition("\n");

    expect($this->tracker->getLine())->toBe(2)
        ->and($this->tracker->getColumn())->toBe(0);
});

it('handles text ending with newline', function () {
    $this->tracker->updatePosition("text\n");

    expect($this->tracker->getLine())->toBe(2)
        ->and($this->tracker->getColumn())->toBe(0);
});

it('handles consecutive newlines', function () {
    $this->tracker->updatePosition("\n\n\n");

    expect($this->tracker->getLine())->toBe(4)
        ->and($this->tracker->getColumn())->toBe(0);
});

it('works in complex scenario', function () {
    $this->tracker->setSourceCode("    line1\n  line2\n\tline3");

    expect($this->tracker->getLine())->toBe(1)
        ->and($this->tracker->getColumn())->toBe(0);

    $this->tracker->updatePosition("partial\nline content\nwith multiple\nlines");

    expect($this->tracker->getLine())->toBe(4)
        ->and($this->tracker->getColumn())->toBe(5)
        ->and($this->tracker->calculateIndentation(1))->toBe(4)
        ->and($this->tracker->calculateIndentation(2))->toBe(2)
        ->and($this->tracker->calculateIndentation(3))->toBe(1);

    $savedState = $this->tracker->getState();
    $this->tracker->updatePosition('more');

    expect($this->tracker->getColumn())->toBe(9);

    $this->tracker->setState($savedState);

    expect($this->tracker->getLine())->toBe(4)
        ->and($this->tracker->getColumn())->toBe(5);
});
