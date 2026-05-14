<?php

use Illuminate\Support\Facades\Http;
use NextMigrant\Plunk\Data\Template;
use NextMigrant\Plunk\Plunk;

it('lists templates with pagination', function () {
    Http::fake([
        '*/templates*' => Http::response([
            'templates' => [
                ['id' => 't_1', 'name' => 'Welcome', 'subject' => 'Welcome!', 'body' => '<h1>Hi</h1>', 'type' => 'TRANSACTIONAL'],
                ['id' => 't_2', 'name' => 'Newsletter', 'subject' => 'News', 'body' => '<p>News</p>', 'type' => 'MARKETING'],
            ],
            'total' => 2,
            'page' => 1,
            'pageSize' => 20,
            'totalPages' => 1,
        ]),
    ]);

    $result = Plunk::templates()->list(type: 'TRANSACTIONAL');

    expect($result['templates'])->toHaveCount(2)
        ->and($result['templates'][0])->toBeInstanceOf(Template::class)
        ->and($result['templates'][0]->name)->toBe('Welcome')
        ->and($result['total'])->toBe(2);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'type=TRANSACTIONAL');
    });
});

it('gets a single template', function () {
    Http::fake([
        '*/templates/t_1' => Http::response([
            'id' => 't_1',
            'name' => 'Welcome',
            'subject' => 'Welcome!',
            'body' => '<h1>Hi</h1>',
            'type' => 'TRANSACTIONAL',
            'createdAt' => '2026-01-01T00:00:00Z',
        ]),
    ]);

    $template = Plunk::templates()->get('t_1');

    expect($template)->toBeInstanceOf(Template::class)
        ->and($template->id)->toBe('t_1')
        ->and($template->subject)->toBe('Welcome!');
});

it('creates a template', function () {
    Http::fake([
        '*/templates' => Http::response([
            'id' => 't_new',
            'name' => 'Reset Password',
            'subject' => 'Reset your password',
            'body' => '<p>Click here</p>',
            'type' => 'TRANSACTIONAL',
            'createdAt' => '2026-01-01T00:00:00Z',
        ], 201),
    ]);

    $template = Plunk::templates()->create(
        name: 'Reset Password',
        subject: 'Reset your password',
        body: '<p>Click here</p>',
    );

    expect($template)->toBeInstanceOf(Template::class)
        ->and($template->name)->toBe('Reset Password');

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request['name'] === 'Reset Password'
            && $request['type'] === 'TRANSACTIONAL';
    });
});

it('updates a template', function () {
    Http::fake([
        '*/templates/t_1' => Http::response([
            'id' => 't_1',
            'name' => 'Updated',
            'subject' => 'New Subject',
            'body' => '<p>Updated</p>',
            'type' => 'TRANSACTIONAL',
        ]),
    ]);

    $template = Plunk::templates()->update('t_1', subject: 'New Subject');

    expect($template->subject)->toBe('New Subject');

    Http::assertSent(function ($request) {
        return $request->method() === 'PATCH'
            && str_contains($request->url(), '/templates/t_1')
            && $request['subject'] === 'New Subject';
    });
});

it('deletes a template', function () {
    Http::fake([
        '*/templates/t_1' => Http::response(null, 204),
    ]);

    Plunk::templates()->delete('t_1');

    Http::assertSent(function ($request) {
        return $request->method() === 'DELETE'
            && str_contains($request->url(), '/templates/t_1');
    });
});

it('duplicates a template', function () {
    Http::fake([
        '*/templates/t_1/duplicate' => Http::response([
            'id' => 't_copy',
            'name' => 'Welcome (copy)',
            'subject' => 'Welcome!',
            'body' => '<h1>Hi</h1>',
            'type' => 'TRANSACTIONAL',
        ]),
    ]);

    $template = Plunk::templates()->duplicate('t_1');

    expect($template)->toBeInstanceOf(Template::class)
        ->and($template->id)->toBe('t_copy');
});

it('gets template usage', function () {
    Http::fake([
        '*/templates/t_1/usage' => Http::response([
            'campaigns' => ['c_1'],
            'workflows' => ['w_1'],
        ]),
    ]);

    $usage = Plunk::templates()->usage('t_1');

    expect($usage['campaigns'])->toContain('c_1');
});
