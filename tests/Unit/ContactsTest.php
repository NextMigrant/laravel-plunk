<?php

use Illuminate\Support\Facades\Http;
use NextMigrant\Plunk\Data\Contact;
use NextMigrant\Plunk\Plunk;

it('lists contacts', function () {
    Http::fake([
        '*/contacts*' => Http::response([
            'data' => [
                ['id' => 'c_1', 'email' => 'a@example.com', 'subscribed' => true, 'data' => []],
                ['id' => 'c_2', 'email' => 'b@example.com', 'subscribed' => false, 'data' => ['plan' => 'pro']],
            ],
            'cursor' => 'next_cursor',
        ]),
    ]);

    $result = Plunk::contacts()->list();

    expect($result['data'])->toHaveCount(2)
        ->and($result['data'][0])->toBeInstanceOf(Contact::class)
        ->and($result['data'][0]->email)->toBe('a@example.com')
        ->and($result['data'][1]->subscribed)->toBeFalse()
        ->and($result['cursor'])->toBe('next_cursor');
});

it('lists contacts with search and pagination', function () {
    Http::fake([
        '*/contacts*' => Http::response(['data' => []]),
    ]);

    Plunk::contacts()->list(search: 'john', limit: 50, cursor: 'abc');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'search=john')
            && str_contains($request->url(), 'limit=50')
            && str_contains($request->url(), 'cursor=abc');
    });
});

it('gets a single contact', function () {
    Http::fake([
        '*/contacts/c_1' => Http::response([
            'id' => 'c_1',
            'email' => 'user@example.com',
            'subscribed' => true,
            'data' => ['plan' => 'pro'],
            'createdAt' => '2026-01-01T00:00:00Z',
            'updatedAt' => '2026-01-02T00:00:00Z',
        ]),
    ]);

    $contact = Plunk::contacts()->get('c_1');

    expect($contact)->toBeInstanceOf(Contact::class)
        ->and($contact->id)->toBe('c_1')
        ->and($contact->email)->toBe('user@example.com')
        ->and($contact->subscribed)->toBeTrue()
        ->and($contact->data)->toBe(['plan' => 'pro']);
});

it('creates a contact', function () {
    Http::fake([
        '*/contacts' => Http::response([
            'id' => 'c_new',
            'email' => 'new@example.com',
            '_meta' => ['isNew' => true, 'isUpdate' => false],
        ]),
    ]);

    $result = Plunk::contacts()->create('new@example.com', ['source' => 'api']);

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request['email'] === 'new@example.com'
            && $request['data'] === ['source' => 'api'];
    });
});

it('creates a contact with subscribed state', function () {
    Http::fake([
        '*/contacts' => Http::response([
            'id' => 'c_new',
            'email' => 'new@example.com',
            '_meta' => ['isNew' => true],
        ]),
    ]);

    Plunk::contacts()->create('new@example.com', subscribed: true);

    Http::assertSent(function ($request) {
        return $request['email'] === 'new@example.com'
            && $request['subscribed'] === true;
    });
});

it('updates a contact', function () {
    Http::fake([
        '*/contacts/c_1' => Http::response([
            'id' => 'c_1',
            'email' => 'updated@example.com',
            'subscribed' => false,
            'data' => [],
        ]),
    ]);

    $contact = Plunk::contacts()->update('c_1', email: 'updated@example.com', subscribed: false);

    expect($contact)->toBeInstanceOf(Contact::class)
        ->and($contact->email)->toBe('updated@example.com')
        ->and($contact->subscribed)->toBeFalse();

    Http::assertSent(function ($request) {
        return $request->method() === 'PATCH'
            && $request['email'] === 'updated@example.com'
            && $request['subscribed'] === false;
    });
});

it('deletes a contact', function () {
    Http::fake([
        '*/contacts/c_1' => Http::response(['success' => true]),
    ]);

    $result = Plunk::contacts()->delete('c_1');

    expect($result['success'])->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->method() === 'DELETE'
            && str_contains($request->url(), '/contacts/c_1');
    });
});

it('subscribes contacts in bulk', function () {
    Http::fake([
        '*/contacts/bulk-subscribe' => Http::response(['jobId' => 'job_1']),
    ]);

    $result = Plunk::contacts()->bulkSubscribe(['c_1', 'c_2', 'c_3']);

    expect($result['jobId'])->toBe('job_1');

    Http::assertSent(function ($request) {
        return $request['ids'] === ['c_1', 'c_2', 'c_3'];
    });
});

it('unsubscribes contacts in bulk', function () {
    Http::fake([
        '*/contacts/bulk-unsubscribe' => Http::response(['jobId' => 'job_2']),
    ]);

    $result = Plunk::contacts()->bulkUnsubscribe(['c_1', 'c_2']);

    expect($result['jobId'])->toBe('job_2');
});

it('deletes contacts in bulk', function () {
    Http::fake([
        '*/contacts/bulk-delete' => Http::response(['jobId' => 'job_3']),
    ]);

    $result = Plunk::contacts()->bulkDelete(['c_1']);

    expect($result['jobId'])->toBe('job_3');
});

it('checks bulk job status', function () {
    Http::fake([
        '*/contacts/bulk/job_1' => Http::response(['status' => 'completed', 'processed' => 3]),
    ]);

    $result = Plunk::contacts()->bulkStatus('job_1');

    expect($result['status'])->toBe('completed')
        ->and($result['processed'])->toBe(3);
});

it('throws when importing a non-existent file', function () {
    Plunk::contacts()->import('/nonexistent/file.csv');
})->throws(InvalidArgumentException::class);

it('throws when importing a file exceeding 5MB', function () {
    // Create a temp file larger than 5MB in the project directory
    $tmpPath = __DIR__.'/../../large_test.csv';
    file_put_contents($tmpPath, str_repeat('a', 5 * 1024 * 1024 + 1));

    try {
        Plunk::contacts()->import($tmpPath);
    } catch (InvalidArgumentException $e) {
        expect($e->getMessage())->toContain('5MB');

        return;
    } finally {
        unlink($tmpPath);
    }

    $this->fail('Expected InvalidArgumentException was not thrown.');
});
