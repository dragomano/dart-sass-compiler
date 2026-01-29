<?php

declare(strict_types=1);

use DartSass\Compilers\Environment;
use DartSass\Utils\Scope;

describe('Environment', function () {
    beforeEach(function () {
        $this->environment = new Environment();
    });

    describe('getCurrentScope()', function () {
        it('returns root scope', function () {
            $scope = $this->environment->getCurrentScope();

            expect($scope)->toBeInstanceOf(Scope::class);
        });
    });
});
