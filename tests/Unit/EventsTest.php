<?php

use Illuminate\Support\Facades\Http;
use NextMigrant\Plunk\Exceptions\AuthenticationException;
use NextMigrant\Plunk\Plunk;
use NextMigrant\Plunk\PlunkManager;

it('tracks an event for a contact', function () {
    Http::fake([
        '*/v1/track' => Http::response(['success' => true, 'contact' => 'c_1']),
    ]);

    $result = Plunk::events()->track(
        email: 'user@example.com',
        event: 'signed_up',
    );

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/v1/track')
            && $request['email'] === 'user@example.com'
            && $request['event'] === 'signed_up';
    });
});

it('tracks an event with custom data', function () {
    Http::fake([
        '*/v1/track' => Http::response(['success' => true]),
    ]);

    Plunk::events()->track(
        email: 'user@example.com',
        event: 'plan_upgraded',
        data: ['plan' => 'enterprise', 'seats' => 50],
    );

    Http::assertSent(function ($request) {
        return $request['data'] === ['plan' => 'enterprise', 'seats' => 50];
    });
});

it('tracks an event with subscribed state', function () {
    Http::fake([
        '*/v1/track' => Http::response(['success' => true]),
    ]);

    Plunk::events()->track(
        email: 'user@example.com',
        event: 'signed_up',
        subscribed: false,
    );

    Http::assertSent(function ($request) {
        return $request['subscribed'] === false;
    });
});

it('uses the public key for event tracking', function () {
    Http::fake([
        '*/v1/track' => Http::response(['success' => true]),
    ]);

    Plunk::events()->track(
        email: 'user@example.com',
        event: 'page_viewed',
    );

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer pk_test_public');
    });
});

it('throws when public key is not configured for event tracking', function () {
    $manager = new PlunkManager([
        'secret_key' => 'sk_test',
        'base_api_url' => 'https://next-api.useplunk.com',
    ]);

    $manager->events();
})->throws(AuthenticationException::class, 'PLUNK_PUBLIC_KEY');
