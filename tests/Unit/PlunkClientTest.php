<?php

use Illuminate\Support\Facades\Http;
use NextMigrant\Plunk\Exceptions\AuthenticationException;
use NextMigrant\Plunk\Exceptions\BillingException;
use NextMigrant\Plunk\Exceptions\ConflictException;
use NextMigrant\Plunk\Exceptions\PlunkException;
use NextMigrant\Plunk\Exceptions\RateLimitException;
use NextMigrant\Plunk\Exceptions\ValidationException;
use NextMigrant\Plunk\Plunk;

it('throws AuthenticationException on 401', function () {
    Http::fake([
        '*/v1/send' => Http::response(['message' => 'Invalid API key'], 401),
    ]);

    Plunk::transactional()->send(to: 'a@b.com', subject: 'Test', body: 'Hi');
})->throws(AuthenticationException::class, 'Invalid API key');

it('throws AuthenticationException on 403', function () {
    Http::fake([
        '*/v1/send' => Http::response(['message' => 'Forbidden'], 403),
    ]);

    Plunk::transactional()->send(to: 'a@b.com', subject: 'Test', body: 'Hi');
})->throws(AuthenticationException::class, 'Forbidden');

it('throws BillingException on 402', function () {
    Http::fake([
        '*/v1/send' => Http::response([
            'success' => false,
            'error' => [
                'code' => 'BILLING_LIMIT_EXCEEDED',
                'message' => 'You have exceeded your monthly email limit',
                'statusCode' => 402,
                'requestId' => 'req_123',
                'suggestion' => 'Upgrade your plan for higher limits.',
            ],
        ], 402),
    ]);

    Plunk::transactional()->send(to: 'a@b.com', subject: 'Test', body: 'Hi');
})->throws(BillingException::class, 'You have exceeded your monthly email limit');

it('throws ConflictException on 409', function () {
    Http::fake([
        '*/contacts' => Http::response([
            'success' => false,
            'error' => [
                'code' => 'CONFLICT',
                'message' => 'A contact with this email already exists',
                'statusCode' => 409,
                'requestId' => 'req_456',
            ],
        ], 409),
    ]);

    Plunk::contacts()->create('duplicate@example.com');
})->throws(ConflictException::class);

it('throws ValidationException on 422', function () {
    Http::fake([
        '*/v1/send' => Http::response(['message' => 'The to field is required'], 422),
    ]);

    Plunk::transactional()->send(to: '', subject: 'Test', body: 'Hi');
})->throws(ValidationException::class, 'The to field is required');

it('throws RateLimitException on 429', function () {
    Http::fake([
        '*/v1/send' => Http::response(['message' => 'Rate limit exceeded'], 429),
    ]);

    Plunk::transactional()->send(to: 'a@b.com', subject: 'Test', body: 'Hi');
})->throws(RateLimitException::class, 'Rate limit exceeded');

it('throws PlunkException on 500', function () {
    Http::fake([
        '*/v1/send' => Http::response(['error' => 'Internal server error'], 500),
    ]);

    Plunk::transactional()->send(to: 'a@b.com', subject: 'Test', body: 'Hi');
})->throws(PlunkException::class, 'Internal server error');

it('exception contains the response object', function () {
    Http::fake([
        '*/v1/send' => Http::response(['message' => 'Bad request'], 400),
    ]);

    try {
        Plunk::transactional()->send(to: 'a@b.com', subject: 'Test', body: 'Hi');
    } catch (PlunkException $e) {
        expect($e->response)->not->toBeNull()
            ->and($e->response->status())->toBe(400)
            ->and($e->getCode())->toBe(400);

        return;
    }

    $this->fail('Expected PlunkException was not thrown.');
});

it('parses standardized error format with metadata', function () {
    Http::fake([
        '*/v1/send' => Http::response([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Request validation failed',
                'statusCode' => 422,
                'requestId' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
                'errors' => [
                    ['field' => 'email', 'message' => 'Invalid email', 'code' => 'invalid_string'],
                ],
                'suggestion' => 'Check that strings are quoted.',
            ],
            'timestamp' => '2025-11-30T10:30:00.000Z',
        ], 422),
    ]);

    try {
        Plunk::transactional()->send(to: 'bad', subject: 'Test', body: 'Hi');
    } catch (ValidationException $e) {
        expect($e->getMessage())->toBe('Request validation failed')
            ->and($e->errorCode)->toBe('VALIDATION_ERROR')
            ->and($e->requestId)->toBe('f47ac10b-58cc-4372-a567-0e02b2c3d479')
            ->and($e->suggestion)->toBe('Check that strings are quoted.');

        return;
    }

    $this->fail('Expected ValidationException was not thrown.');
});
