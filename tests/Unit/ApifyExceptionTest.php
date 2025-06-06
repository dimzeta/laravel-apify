<?php

use Apify\Laravel\ApifyException;

describe('ApifyException', function () {
    it('can be instantiated with message', function () {
        $exception = new ApifyException('Test error message');

        expect($exception)
            ->toBeInstanceOf(ApifyException::class)
            ->toBeInstanceOf(Exception::class)
            ->and($exception->getMessage())
            ->toBe('Test error message');
    });

    it('can be instantiated with message and code', function () {
        $exception = new ApifyException('Test error', 400);

        expect($exception->getMessage())->toBe('Test error')
            ->and($exception->getCode())->toBe(400);
    });

    it('can be instantiated with message, code, and previous exception', function () {
        $previous = new Exception('Previous exception');
        $exception = new ApifyException('Test error', 500, $previous);

        expect($exception->getMessage())->toBe('Test error')
            ->and($exception->getCode())->toBe(500)
            ->and($exception->getPrevious())->toBe($previous);
    });

    it('can be thrown and caught', function () {
        expect(fn () => throw new ApifyException('Test exception'))
            ->toThrow(ApifyException::class, 'Test exception');
    });

    it('can be caught as generic Exception', function () {
        expect(fn () => throw new ApifyException('Test exception'))
            ->toThrow(Exception::class);
    });
});
