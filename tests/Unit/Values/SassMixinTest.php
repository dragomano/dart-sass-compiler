<?php

declare(strict_types=1);

use DartSass\Handlers\MixinHandler;
use DartSass\Values\SassMixin;

describe('SassMixin', function () {
    describe('__toString()', function () {
        it('returns mixin name', function () {
            $handler = mock(MixinHandler::class);
            $mixin   = new SassMixin($handler, 'myMixin');

            expect((string) $mixin)->toBe('myMixin');
        });
    });

    describe('acceptsContent()', function () {
        it('returns false when mixin definition is null', function () {
            $handler = mock(MixinHandler::class);
            $handler->shouldReceive('getMixins')->andReturn(['mixins' => []]);
            $mixin = new SassMixin($handler, 'nonExistentMixin');

            expect($mixin->acceptsContent())->toBeFalse();
        });

        it('returns true when body contains @content', function () {
            $handler = mock(MixinHandler::class);
            $handler->shouldReceive('getMixins')->andReturn([
                'mixins' => [
                    'myMixin' => ['body' => ['some code', '@content', 'more code']],
                ],
            ]);
            $mixin = new SassMixin($handler, 'myMixin');

            expect($mixin->acceptsContent())->toBeTrue();
        });

        it('returns false when body does not contain @content', function () {
            $handler = mock(MixinHandler::class);
            $handler->shouldReceive('getMixins')->andReturn([
                'mixins' => [
                    'myMixin' => ['body' => ['some code', 'more code']],
                ],
            ]);
            $mixin = new SassMixin($handler, 'myMixin');

            expect($mixin->acceptsContent())->toBeFalse();
        });
    });
})->covers(SassMixin::class);
