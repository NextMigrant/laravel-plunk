<?php

use Illuminate\Support\Facades\Http;
use NextMigrant\Plunk\Plunk;

it('lists all segments', function () {
    Http::fake([
        '*/segments' => Http::response([
            ['id' => 's_1', 'name' => 'Active Users', 'memberCount' => 500],
            ['id' => 's_2', 'name' => 'Churned', 'memberCount' => 50],
        ]),
    ]);

    $segments = Plunk::segments()->list();

    expect($segments)->toHaveCount(2);
});

it('gets a single segment', function () {
    Http::fake([
        '*/segments/s_1' => Http::response([
            'id' => 's_1',
            'name' => 'Active Users',
            'filters' => ['subscribed' => true],
            'trackMembership' => true,
            'memberCount' => 500,
        ]),
    ]);

    $segment = Plunk::segments()->get('s_1');

    expect($segment['name'])->toBe('Active Users')
        ->and($segment['memberCount'])->toBe(500);
});

it('creates a segment', function () {
    Http::fake([
        '*/segments' => Http::response([
            'id' => 's_new',
            'name' => 'Pro Users',
            'filters' => ['data.plan' => 'pro'],
            'trackMembership' => false,
            'memberCount' => 0,
        ], 201),
    ]);

    $result = Plunk::segments()->create(
        name: 'Pro Users',
        filters: ['data.plan' => 'pro'],
    );

    expect($result['id'])->toBe('s_new');

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request['name'] === 'Pro Users';
    });
});

it('updates a segment', function () {
    Http::fake([
        '*/segments/s_1' => Http::response([
            'id' => 's_1',
            'name' => 'Updated Name',
        ]),
    ]);

    Plunk::segments()->update('s_1', ['name' => 'Updated Name']);

    Http::assertSent(function ($request) {
        return $request->method() === 'PATCH'
            && $request['name'] === 'Updated Name';
    });
});

it('deletes a segment', function () {
    Http::fake([
        '*/segments/s_1' => Http::response(null, 204),
    ]);

    Plunk::segments()->delete('s_1');

    Http::assertSent(function ($request) {
        return $request->method() === 'DELETE'
            && str_contains($request->url(), '/segments/s_1');
    });
});

it('lists segment contacts with pagination', function () {
    Http::fake([
        '*/segments/s_1/contacts*' => Http::response([
            'data' => [['id' => 'c_1', 'email' => 'a@example.com']],
            'total' => 500,
            'page' => 1,
            'pageSize' => 100,
        ]),
    ]);

    $result = Plunk::segments()->contacts('s_1', page: 1, pageSize: 100);

    expect($result['total'])->toBe(500);
});

it('adds members to a static segment', function () {
    Http::fake([
        '*/segments/s_1/members' => Http::response(['added' => 2]),
    ]);

    Plunk::segments()->addMembers('s_1', ['a@example.com', 'b@example.com'], createMissing: true);

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request['emails'] === ['a@example.com', 'b@example.com']
            && $request['createMissing'] === true;
    });
});

it('removes members from a static segment', function () {
    Http::fake([
        '*/segments/s_1/members' => Http::response(['removed' => 1]),
    ]);

    Plunk::segments()->removeMembers('s_1', ['a@example.com']);

    Http::assertSent(function ($request) {
        return $request->method() === 'DELETE'
            && $request['emails'] === ['a@example.com'];
    });
});

it('computes segment membership', function () {
    Http::fake([
        '*/segments/s_1/compute' => Http::response(['success' => true, 'memberCount' => 450]),
    ]);

    $result = Plunk::segments()->compute('s_1');

    expect($result['success'])->toBeTrue();
});

it('refreshes segment count', function () {
    Http::fake([
        '*/segments/s_1/refresh' => Http::response(['memberCount' => 450]),
    ]);

    $result = Plunk::segments()->refresh('s_1');

    expect($result['memberCount'])->toBe(450);
});
