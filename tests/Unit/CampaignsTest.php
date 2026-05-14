<?php

use Illuminate\Support\Facades\Http;
use NextMigrant\Plunk\Plunk;

it('lists all campaigns', function () {
    Http::fake([
        '*/campaigns' => Http::response([
            ['id' => 'c_1', 'name' => 'Welcome', 'status' => 'DRAFT'],
            ['id' => 'c_2', 'name' => 'Newsletter', 'status' => 'SENT'],
        ]),
    ]);

    $campaigns = Plunk::campaigns()->list();

    expect($campaigns)->toHaveCount(2);
});

it('gets a single campaign', function () {
    Http::fake([
        '*/campaigns/c_1' => Http::response([
            'id' => 'c_1',
            'name' => 'Welcome',
            'subject' => 'Welcome!',
            'status' => 'DRAFT',
        ]),
    ]);

    $campaign = Plunk::campaigns()->get('c_1');

    expect($campaign['id'])->toBe('c_1');
});

it('creates a campaign', function () {
    Http::fake([
        '*/campaigns' => Http::response([
            'success' => true,
            'data' => [
                'id' => 'c_new',
                'name' => 'Launch',
                'status' => 'DRAFT',
            ],
        ]),
    ]);

    $result = Plunk::campaigns()->create(
        name: 'Launch',
        subject: 'We launched!',
        body: '<h1>Big news</h1>',
        from: 'hello@acme.com',
        audienceType: 'ALL',
    );

    expect($result['success'])->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request['name'] === 'Launch'
            && $request['audienceType'] === 'ALL';
    });
});

it('sends a campaign', function () {
    Http::fake([
        '*/campaigns/c_1/send' => Http::response(['success' => true]),
    ]);

    $result = Plunk::campaigns()->send('c_1');

    expect($result['success'])->toBeTrue();
});

it('schedules a campaign', function () {
    Http::fake([
        '*/campaigns/c_1/send' => Http::response(['success' => true]),
    ]);

    Plunk::campaigns()->send('c_1', scheduledFor: '2026-06-01T10:00:00Z');

    Http::assertSent(function ($request) {
        return $request['scheduledFor'] === '2026-06-01T10:00:00Z';
    });
});

it('cancels a campaign', function () {
    Http::fake([
        '*/campaigns/c_1/cancel' => Http::response(['success' => true]),
    ]);

    $result = Plunk::campaigns()->cancel('c_1');

    expect($result['success'])->toBeTrue();
});

it('sends a test email for a campaign', function () {
    Http::fake([
        '*/campaigns/c_1/test' => Http::response(['success' => true]),
    ]);

    Plunk::campaigns()->test('c_1', 'tester@example.com');

    Http::assertSent(function ($request) {
        return $request['email'] === 'tester@example.com';
    });
});

it('gets campaign stats', function () {
    Http::fake([
        '*/campaigns/c_1/stats' => Http::response([
            'sent' => 1000,
            'opened' => 450,
            'clicked' => 120,
            'bounced' => 5,
        ]),
    ]);

    $stats = Plunk::campaigns()->stats('c_1');

    expect($stats['sent'])->toBe(1000)
        ->and($stats['opened'])->toBe(450);
});

it('duplicates a campaign', function () {
    Http::fake([
        '*/campaigns/c_1/duplicate' => Http::response([
            'id' => 'c_copy',
            'name' => 'Welcome (copy)',
            'status' => 'DRAFT',
        ]),
    ]);

    $result = Plunk::campaigns()->duplicate('c_1');

    expect($result['id'])->toBe('c_copy')
        ->and($result['status'])->toBe('DRAFT');
});

it('deletes a campaign', function () {
    Http::fake([
        '*/campaigns/c_1' => Http::response(null, 204),
    ]);

    Plunk::campaigns()->delete('c_1');

    Http::assertSent(function ($request) {
        return $request->method() === 'DELETE'
            && str_contains($request->url(), '/campaigns/c_1');
    });
});
