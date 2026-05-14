<?php

use Illuminate\Support\Facades\Http;
use NextMigrant\Plunk\Plunk;

it('sends a transactional email with required fields', function () {
    Http::fake([
        '*/v1/send' => Http::response(['success' => true, 'emails' => [['contact' => ['id' => 'c_1']]]]),
    ]);

    $result = Plunk::transactional()->send(
        to: 'user@example.com',
        subject: 'Welcome!',
        body: '<h1>Hello</h1>',
    );

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/v1/send')
            && $request['to'] === 'user@example.com'
            && $request['subject'] === 'Welcome!'
            && $request['body'] === '<h1>Hello</h1>'
            && $request->hasHeader('Authorization', 'Bearer sk_test_secret');
    });
});

it('sends a transactional email with all optional fields', function () {
    Http::fake([
        '*/v1/send' => Http::response(['success' => true]),
    ]);

    Plunk::transactional()->send(
        to: ['a@example.com', 'b@example.com'],
        subject: 'Hello',
        body: '<p>Hi</p>',
        from: 'sender@verified-domain.com',
        name: 'My App',
        reply: 'reply@example.com',
        cc: ['cc@example.com'],
        bcc: ['bcc@example.com'],
        headers: ['X-Custom' => 'value'],
        attachments: [['filename' => 'doc.pdf', 'content' => base64_encode('pdf')]],
        subscribed: true,
        data: ['plan' => 'pro'],
    );

    Http::assertSent(function ($request) {
        return $request['to'] === ['a@example.com', 'b@example.com']
            && $request['from'] === 'sender@verified-domain.com'
            && $request['name'] === 'My App'
            && $request['reply'] === 'reply@example.com'
            && $request['cc'] === ['cc@example.com']
            && $request['bcc'] === ['bcc@example.com']
            && $request['headers'] === ['X-Custom' => 'value']
            && $request['subscribed'] === true
            && $request['data'] === ['plan' => 'pro'];
    });
});

it('sends a transactional email using a template', function () {
    Http::fake([
        '*/v1/send' => Http::response(['success' => true]),
    ]);

    Plunk::transactional()->send(
        to: 'user@example.com',
        subject: 'Welcome',
        template: 'tpl_12345',
    );

    Http::assertSent(function ($request) {
        return $request['template'] === 'tpl_12345'
            && ! isset($request['body']);
    });
});

it('omits empty optional arrays from the payload', function () {
    Http::fake([
        '*/v1/send' => Http::response(['success' => true]),
    ]);

    Plunk::transactional()->send(
        to: 'user@example.com',
        subject: 'Test',
        body: '<p>Hi</p>',
    );

    Http::assertSent(function ($request) {
        $body = $request->data();

        return ! array_key_exists('cc', $body)
            && ! array_key_exists('bcc', $body)
            && ! array_key_exists('headers', $body)
            && ! array_key_exists('attachments', $body)
            && ! array_key_exists('data', $body);
    });
});
