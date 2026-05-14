<?php

use Illuminate\Support\Facades\Http;
use NextMigrant\Plunk\Data\Contact;
use NextMigrant\Plunk\Plunk;

it('lists contacts with cursor pagination', function () {
    Http::fake([
        '*/contacts*' => Http::response([
            'data' => [
                ['id' => 'c_1', 'email' => 'a@example.com', 'subscribed' => true, 'data' => '{}'],
                ['id' => 'c_2', 'email' => 'b@example.com', 'subscribed' => false, 'data' => '{"plan":"pro"}'],
            ],
            'cursor' => 'abc123',
            'hasMore' => true,
            'total' => 100,
        ]),
    ]);

    $result = Plunk::contacts()->list(search: 'example', limit: 50);

    expect($result['data'])->toHaveCount(2)
        ->and($result['data'][0])->toBeInstanceOf(Contact::class)
        ->and($result['data'][0]->email)->toBe('a@example.com')
        ->and($result['hasMore'])->toBeTrue()
        ->and($result['cursor'])->toBe('abc123');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'search=example')
            && str_contains($request->url(), 'limit=50');
    });
});

it('gets a single contact', function () {
    Http::fake([
        '*/contacts/c_1' => Http::response([
            'id' => 'c_1',
            'email' => 'user@example.com',
            'subscribed' => true,
            'data' => '{"plan":"pro"}',
            'createdAt' => '2026-01-01T00:00:00Z',
            'updatedAt' => '2026-01-02T00:00:00Z',
        ]),
    ]);

    $contact = Plunk::contacts()->get('c_1');

    expect($contact)->toBeInstanceOf(Contact::class)
        ->and($contact->id)->toBe('c_1')
        ->and($contact->email)->toBe('user@example.com')
        ->and($contact->subscribed)->toBeTrue();
});

it('creates a contact', function () {
    Http::fake([
        '*/contacts' => Http::response([
            'id' => 'c_new',
            'email' => 'new@example.com',
            'subscribed' => true,
            '_meta' => ['isNew' => true, 'isUpdate' => false],
        ]),
    ]);

    $result = Plunk::contacts()->create('new@example.com', data: ['source' => 'api']);

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request['email'] === 'new@example.com'
            && $request['data'] === ['source' => 'api'];
    });
});

it('creates a contact with subscribed state', function () {
    Http::fake([
        '*/contacts' => Http::response(['id' => 'c_new']),
    ]);

    Plunk::contacts()->create('new@example.com', subscribed: false);

    Http::assertSent(function ($request) {
        return $request['email'] === 'new@example.com'
            && $request['subscribed'] === false;
    });
});

it('updates a contact by id using PATCH', function () {
    Http::fake([
        '*/contacts/c_1' => Http::response([
            'id' => 'c_1',
            'email' => 'user@example.com',
            'subscribed' => false,
        ]),
    ]);

    $result = Plunk::contacts()->update('c_1', subscribed: false);

    Http::assertSent(function ($request) {
        return $request->method() === 'PATCH'
            && str_contains($request->url(), '/contacts/c_1')
            && $request['subscribed'] === false;
    });
});

it('deletes a contact', function () {
    Http::fake([
        '*/contacts/c_1' => Http::response(null, 204),
    ]);

    Plunk::contacts()->delete('c_1');

    Http::assertSent(function ($request) {
        return $request->method() === 'DELETE'
            && str_contains($request->url(), '/contacts/c_1');
    });
});

it('looks up emails in bulk', function () {
    Http::fake([
        '*/contacts/lookup' => Http::response([
            'results' => [
                ['email' => 'a@example.com', 'exists' => true],
                ['email' => 'b@example.com', 'exists' => false],
            ],
        ]),
    ]);

    $result = Plunk::contacts()->lookup(['a@example.com', 'b@example.com']);

    Http::assertSent(function ($request) {
        return $request['emails'] === ['a@example.com', 'b@example.com'];
    });
});

it('bulk subscribes contacts', function () {
    Http::fake([
        '*/contacts/bulk-subscribe' => Http::response(['jobId' => 'job_1']),
    ]);

    $result = Plunk::contacts()->bulkSubscribe(['id_1', 'id_2']);

    expect($result['jobId'])->toBe('job_1');
});

it('bulk unsubscribes contacts', function () {
    Http::fake([
        '*/contacts/bulk-unsubscribe' => Http::response(['jobId' => 'job_2']),
    ]);

    $result = Plunk::contacts()->bulkUnsubscribe(['id_1', 'id_2']);

    expect($result['jobId'])->toBe('job_2');
});

it('bulk deletes contacts', function () {
    Http::fake([
        '*/contacts/bulk-delete' => Http::response(['jobId' => 'job_3']),
    ]);

    $result = Plunk::contacts()->bulkDelete(['id_1']);

    expect($result['jobId'])->toBe('job_3');
});

it('checks bulk job status', function () {
    Http::fake([
        '*/contacts/bulk/job_1' => Http::response(['status' => 'completed', 'processed' => 100]),
    ]);

    $status = Plunk::contacts()->bulkStatus('job_1');

    expect($status['status'])->toBe('completed');
});

it('checks import job status', function () {
    Http::fake([
        '*/contacts/import/job_1' => Http::response(['status' => 'completed', 'created' => 50]),
    ]);

    $status = Plunk::contacts()->importStatus('job_1');

    expect($status['status'])->toBe('completed');
});

it('throws when importing a non-existent file', function () {
    Plunk::contacts()->import('/nonexistent/file.csv');
})->throws(InvalidArgumentException::class);

it('throws when importing a file exceeding 5MB', function () {
    $tmpPath = __DIR__ . '/../../large_test.csv';
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
