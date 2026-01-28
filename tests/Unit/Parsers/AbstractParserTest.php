<?php

declare(strict_types=1);

use DartSass\Parsers\AbstractParser;
use DartSass\Parsers\ParserInterface;
use DartSass\Parsers\Tokens\TokenAwareParserInterface;
use DartSass\Parsers\Tokens\TokenStreamHelper;
use DartSass\Parsers\Tokens\TokenStreamInterface;

describe('AbstractParser', function () {
    arch()
        ->expect(AbstractParser::class)
        ->toImplement([TokenAwareParserInterface::class, ParserInterface::class])
        ->toUseTrait(TokenStreamHelper::class);

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
