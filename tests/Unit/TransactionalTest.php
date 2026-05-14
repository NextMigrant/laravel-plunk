<?php

use Illuminate\Support\Facades\Http;
use NextMigrant\Plunk\Plunk;

it('sends a transactional email with required fields', function () {
    Http::fake([
        '*/v1/send' => Http::response([
            'success' => true,
            'data' => [
                'emails' => [['contact' => ['id' => 'c_1', 'email' => 'user@example.com'], 'email' => 'e_1']],
                'timestamp' => '2026-05-14T00:00:00Z',
            ],
        ]),
    ]);

    $result = Plunk::transactional()->send(
        to: 'user@example.com',
        subject: 'Welcome!',
        body: '<h1>Hello</h1>',
    );

    expect($result['success'])->toBeTrue()
        ->and($result['data']['emails'])->toHaveCount(1);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/v1/send')
            && $request['to'] === 'user@example.com'
            && $request['subject'] === 'Welcome!'
            && $request['body'] === '<h1>Hello</h1>'
            && $request->hasHeader('Authorization', 'Bearer sk_test_secret');
    });
});

it('sends a transactional email using a template', function () {
    Http::fake([
        '*/v1/send' => Http::response(['success' => true, 'data' => ['emails' => []]]),
    ]);

    Plunk::transactional()->send(
        to: 'user@example.com',
        template: 'tpl_welcome',
        data: ['firstName' => 'John', 'plan' => 'pro'],
    );

    Http::assertSent(function ($request) {
        return $request['template'] === 'tpl_welcome'
            && $request['data'] === ['firstName' => 'John', 'plan' => 'pro']
            && ! array_key_exists('subject', $request->data())
            && ! array_key_exists('body', $request->data());
    });
});

it('sends with all optional fields', function () {
    Http::fake([
        '*/v1/send' => Http::response(['success' => true, 'data' => ['emails' => []]]),
    ]);

    Plunk::transactional()->send(
        to: [['name' => 'John', 'email' => 'john@example.com']],
        subject: 'Hello',
        body: '<p>Hi</p>',
        from: ['name' => 'My App', 'email' => 'hello@verified.com'],
        reply: 'reply@example.com',
        subscribed: true,
        data: ['plan' => 'enterprise'],
        headers: ['X-Custom' => 'value'],
        attachments: [
            [
                'filename' => 'doc.pdf',
                'content' => base64_encode('pdf'),
                'contentType' => 'application/pdf',
            ],
        ],
    );

    Http::assertSent(function ($request) {
        return $request['from'] === ['name' => 'My App', 'email' => 'hello@verified.com']
            && $request['reply'] === 'reply@example.com'
            && $request['subscribed'] === true
            && $request['data'] === ['plan' => 'enterprise']
            && $request['headers'] === ['X-Custom' => 'value'];
    });
});

it('omits empty optional fields from the payload', function () {
    Http::fake([
        '*/v1/send' => Http::response(['success' => true, 'data' => ['emails' => []]]),
    ]);

    Plunk::transactional()->send(
        to: 'user@example.com',
        subject: 'Test',
        body: '<p>Hi</p>',
    );

    Http::assertSent(function ($request) {
        $body = $request->data();

        return ! array_key_exists('headers', $body)
            && ! array_key_exists('attachments', $body)
            && ! array_key_exists('from', $body)
            && ! array_key_exists('template', $body)
            && ! array_key_exists('data', $body)
            && ! array_key_exists('reply', $body);
    });
});
