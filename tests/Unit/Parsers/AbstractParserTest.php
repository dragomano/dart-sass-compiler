<?php

declare(strict_types=1);

use DartSass\Parsers\AbstractParser;
use DartSass\Parsers\Tokens\TokenStreamInterface;

describe('AbstractParser', function () {
    beforeEach(function () {
        $this->stream = mock(TokenStreamInterface::class);

        $this->createParser = function (): AbstractParser {
            return new class ($this->stream) extends AbstractParser {
                public function parse(): null
                {
                    return null;
                }
            };
        };
    });

    describe('parseExpression()', function () {
        it('returns null by default', function () {
            $parser = ($this->createParser)();

            $result = $parser->parseExpression();

            expect($result)->toBeNull();
        });
    });
});
