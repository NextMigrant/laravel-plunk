<?php

use Illuminate\Support\Facades\Http;
use NextMigrant\Plunk\Exceptions\AuthenticationException;
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
