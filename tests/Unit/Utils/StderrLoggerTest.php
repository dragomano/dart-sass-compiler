<?php

declare(strict_types=1);

use DartSass\Utils\StderrLogger;

describe('StderrLogger', function () {
    beforeEach(function () {
        $this->logger = new StderrLogger();
    });

    it('implements LoggerInterface', function () {
        expect($this->logger)->toBeInstanceOf(StderrLogger::class);
    });

    it('debug method returns void', function () {
        $result = $this->logger->debug('debug message');
        expect($result)->toBeNull();
    });

    it('debug method accepts context with file and line', function () {
        $result = $this->logger->debug('debug message', ['file' => 'test.scss', 'line' => 10]);
        expect($result)->toBeNull();
    });

    it('warn method returns void', function () {
        $result = $this->logger->warn('warning message');
        expect($result)->toBeNull();
    });

    it('warn method accepts context with file and line', function () {
        $result = $this->logger->warn('warning message', ['file' => 'test.scss', 'line' => 15]);
        expect($result)->toBeNull();
    });

    it('error method returns void', function () {
        $result = $this->logger->error('error message');
        expect($result)->toBeNull();
    });

    it('error method accepts context with file and line', function () {
        $result = $this->logger->error('error message', ['file' => 'test.scss', 'line' => 20]);
        expect($result)->toBeNull();
    });

    it('handles empty message', function () {
        expect($this->logger->debug(''))->toBeNull();
    });

    it('handles message with special characters', function () {
        expect($this->logger->debug('Message with "quotes" and newlines'))->toBeNull();
    });

    it('handles context with extra keys', function () {
        expect($this->logger->debug('test', ['file' => 'test.scss', 'line' => 10, 'extra' => 'value']))->toBeNull();
    });

    it('handles context without file key', function () {
        expect($this->logger->debug('test', ['line' => 10]))->toBeNull();
    });

    it('handles context without line key', function () {
        expect($this->logger->debug('test', ['file' => 'test.scss']))->toBeNull();
    });

    it('handles empty context array', function () {
        expect($this->logger->debug('test', []))->toBeNull();
    });

    it('handles very long message', function () {
        $longMessage = str_repeat('a', 10000);
        expect($this->logger->debug($longMessage))->toBeNull();
    });

    it('handles multiline message', function () {
        $multilineMessage = "Line 1\nLine 2\nLine 3";
        expect($this->logger->debug($multilineMessage))->toBeNull();
    });

    it('handles message with unicode characters', function () {
        $unicodeMessage = 'Привет мир 🌍';
        expect($this->logger->debug($unicodeMessage))->toBeNull();
    });

    it('handles context with unicode in file path', function () {
        expect($this->logger->debug('test', ['file' => 'файл.scss', 'line' => 1]))->toBeNull();
    });

    it('handles zero values in context', function () {
        expect($this->logger->debug('test', ['file' => 'test.scss', 'line' => 0]))->toBeNull();
    });

    it('handles negative line number in context', function () {
        expect($this->logger->debug('test', ['file' => 'test.scss', 'line' => -1]))->toBeNull();
    });
});
