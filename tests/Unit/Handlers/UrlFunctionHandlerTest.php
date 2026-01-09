<?php

declare(strict_types=1);

use DartSass\Handlers\ModuleHandlers\UrlFunctionHandler;
use DartSass\Handlers\SassModule;

beforeEach(function () {
    $this->handler = new UrlFunctionHandler();
});

describe('UrlFunctionHandler', function () {
    describe('constructor', function () {
        it('creates instance', function () {
            expect($this->handler)->toBeInstanceOf(UrlFunctionHandler::class);
        });
    });

    describe('canHandle method', function () {
        it('returns true for url function', function () {
            expect($this->handler->canHandle('url'))->toBeTrue();
        });

        it('returns false for other functions', function () {
            expect($this->handler->canHandle('calc'))->toBeFalse()
                ->and($this->handler->canHandle('rgb'))->toBeFalse();
        });
    });

    describe('getSupportedFunctions method', function () {
        it('returns array with url', function () {
            expect($this->handler->getSupportedFunctions())->toEqual(['url']);
        });
    });

    describe('getModuleNamespace method', function () {
        it('returns css', function () {
            expect($this->handler->getModuleNamespace())->toEqual(SassModule::CSS);
        });
    });

    describe('getModuleFunctions method', function () {
        it('returns empty array for UrlFunctionHandler', function () {
            expect($this->handler->getModuleFunctions())->toEqual([]);
        });
    });

    describe('handle method', function () {
        it('handles simple URL string', function () {
            $result = $this->handler->handle('url', ['path/to/file.css']);
            expect($result)->toEqual('url(path/to/file.css)');
        });

        it('handles quoted URL string', function () {
            $result = $this->handler->handle('url', ['"path/to/file.css"']);
            expect($result)->toEqual('url("path/to/file.css")');
        });

        it('handles array with quoted true', function () {
            $result = $this->handler->handle('url', [['value' => 'path/to/file.css', 'quoted' => true]]);
            expect($result)->toEqual('url("path/to/file.css")');
        });

        it('handles array with quoted false', function () {
            $result = $this->handler->handle('url', [['value' => 'path/to/file.css', 'quoted' => false]]);
            expect($result)->toEqual('url(path/to/file.css)');
        });

        it('handles absolute URL', function () {
            $result = $this->handler->handle('url', ['https://example.com/style.css']);
            expect($result)->toEqual('url(https://example.com/style.css)');
        });

        it('handles relative URL', function () {
            $result = $this->handler->handle('url', ['./style.css']);
            expect($result)->toEqual('url(./style.css)');
        });

        it('handles URL with calc function', function () {
            $result = $this->handler->handle('url', ['calc(100% - 20px)']);
            expect($result)->toEqual('url(calc(100% - 20px))');
        });

        it('handles quoted URL with calc', function () {
            $result = $this->handler->handle('url', ['"calc(100% - 20px)"']);
            expect($result)->toEqual('url("calc(100% - 20px)")');
        });

        it('handles URL with double quotes inside', function () {
            $result = $this->handler->handle('url', [['value' => 'image"with"quotes.png', 'quoted' => true]]);
            expect($result)->toEqual('url("image\'with\'quotes.png")');
        });

        it('handles URL with spaces', function () {
            $result = $this->handler->handle('url', ['  path/to/file.css  ']);
            expect($result)->toEqual('url(path/to/file.css)');
        });

        it('handles empty URL', function () {
            $result = $this->handler->handle('url', ['']);
            expect($result)->toEqual('url()');
        });

        it('throws exception for no arguments', function () {
            expect(fn() => $this->handler->handle('url', []))
                ->toThrow(
                    InvalidArgumentException::class,
                    'url() function expects exactly one argument'
                );
        });

        it('throws exception for multiple arguments', function () {
            expect(fn() => $this->handler->handle('url', ['arg1', 'arg2']))
                ->toThrow(
                    InvalidArgumentException::class,
                    'url() function expects exactly one argument'
                );
        });

        it('throws exception for invalid argument type', function () {
            expect(fn() => $this->handler->handle('url', [123]))
                ->toThrow(
                    InvalidArgumentException::class,
                    'url() argument must be a string or array with value and quoted status'
                );
        });

        it('throws exception for array without required keys', function () {
            expect(fn() => $this->handler->handle('url', [['value' => 'test']]))
                ->toThrow(
                    InvalidArgumentException::class,
                    'url() argument must be a string or array with value and quoted status'
                );
        });
    });
})->covers(UrlFunctionHandler::class);
