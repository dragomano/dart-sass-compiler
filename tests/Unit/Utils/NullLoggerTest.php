<?php

declare(strict_types=1);

use DartSass\Utils\NullLogger;

describe('NullLogger', function () {
    beforeEach(function () {
        $this->logger = new NullLogger();
    });

    it('implements LoggerInterface', function () {
        expect($this->logger)->toBeInstanceOf(NullLogger::class);
    });

    it('debug method returns null', function () {
        $result = $this->logger->debug('test message');
        expect($result)->toBeNull();
    });

    it('debug method accepts context parameter', function () {
        $result = $this->logger->debug('test message', ['file' => 'test.scss', 'line' => 10]);
        expect($result)->toBeNull();
    });

    it('warn method returns null', function () {
        $result = $this->logger->warn('warning message');
        expect($result)->toBeNull();
    });

    it('warn method accepts context parameter', function () {
        $result = $this->logger->warn('warning message', ['file' => 'test.scss', 'line' => 15]);
        expect($result)->toBeNull();
    });

    it('error method returns null', function () {
        $result = $this->logger->error('error message');
        expect($result)->toBeNull();
    });

    it('error method accepts context parameter', function () {
        $result = $this->logger->error('error message', ['file' => 'test.scss', 'line' => 20]);
        expect($result)->toBeNull();
    });

    it('handles empty message', function () {
        expect($this->logger->debug(''))->toBeNull()
            ->and($this->logger->warn(''))->toBeNull()
            ->and($this->logger->error(''))->toBeNull();
    });

    it('handles complex message with special characters', function () {
        expect($this->logger->debug('Message with "quotes" and \'apostrophes\''))->toBeNull()
            ->and($this->logger->warn("Message with\nnewlines"))->toBeNull()
            ->and($this->logger->error('Message with\ttabs'))->toBeNull();
    });

    it('handles empty context', function () {
        expect($this->logger->debug('test', []))->toBeNull()
            ->and($this->logger->warn('test', []))->toBeNull()
            ->and($this->logger->error('test', []))->toBeNull();
    });

    it('handles context with extra keys', function () {
        $context = ['file' => 'test.scss', 'line' => 10, 'extra' => 'value', 'nested' => ['key' => 'val']];
        expect($this->logger->debug('test', $context))->toBeNull()
            ->and($this->logger->warn('test', $context))->toBeNull()
            ->and($this->logger->error('test', $context))->toBeNull();
    });

    it('handles very long message', function () {
        $longMessage = str_repeat('a', 10000);
        expect($this->logger->debug($longMessage))->toBeNull();
    });
});
