<?php

declare(strict_types=1);

use DartSass\Compiler;

describe('Compiler', function () {
    it('returns options passed during initialization', function () {
        $customOptions = [
            'style'     => 'compressed',
            'sourceMap' => true,
        ];

        $compiler = new Compiler($customOptions);
        $options = $compiler->getContext()->options;

        expect($options)->toBeArray()
            ->and($options['style'])->toBe('compressed')
            ->and($options['sourceMap'])->toBeTrue();
    });

    it('returns default options when no options passed', function () {
        $compiler = new Compiler();
        $options  = $compiler->getContext()->options;

        expect($options)->toBeArray()
            ->and($options['style'])->toBe('expanded')
            ->and($options['sourceMap'])->toBeFalse();
    });
});
