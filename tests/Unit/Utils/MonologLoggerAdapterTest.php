<?php

declare(strict_types=1);

use DartSass\Utils\LoggerInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;

describe('MonologLoggerAdapter', function () {
    beforeEach(function () {
        $this->loggerMock = mock(PsrLoggerInterface::class);

        $this->adapter = new class ($this->loggerMock) implements LoggerInterface {
            public function __construct(private readonly PsrLoggerInterface $logger) {}

            public function debug(string $message, array $context = []): void
            {
                $this->logger->debug($message, $context);
            }

            public function warn(string $message, array $context = []): void
            {
                $this->logger->warning($message, $context);
            }

            public function error(string $message, array $context = []): void
            {
                $this->logger->error($message, $context);
            }
        };
    });

    it('calls warning method on logger when warn is invoked', function () {
        $this->loggerMock->expects('warning')->with('test warn message', ['key' => 'value']);

        $this->adapter->warn('test warn message', ['key' => 'value']);
    });

    it('calls debug method on logger when debug is invoked', function () {
        $this->loggerMock->expects('debug')->with('test debug message', ['ctx' => 'val']);

        $this->adapter->debug('test debug message', ['ctx' => 'val']);
    });

    it('calls error method on logger when error is invoked', function () {
        $this->loggerMock->expects('error')->with('test error message', ['e' => 'rr']);

        $this->adapter->error('test error message', ['e' => 'rr']);
    });
});
