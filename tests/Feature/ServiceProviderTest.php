<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use NextMigrant\Plunk\Data\EmailVerification;
use NextMigrant\Plunk\Exceptions\AuthenticationException;
use NextMigrant\Plunk\Plunk;
use NextMigrant\Plunk\PlunkManager;
use NextMigrant\Plunk\Resources\Contacts;
use NextMigrant\Plunk\Resources\Events;
use NextMigrant\Plunk\Resources\Transactional;

it('registers the PlunkManager singleton in the container', function () {
    $plunk = app(PlunkManager::class);

    expect($plunk)->toBeInstanceOf(PlunkManager::class);
});

it('resolves the same instance from the container', function () {
    $first = app(PlunkManager::class);
    $second = app(PlunkManager::class);

    expect($first)->toBe($second);
});

it('resolves the Plunk facade', function () {
    expect(Plunk::contacts())->toBeInstanceOf(Contacts::class)
        ->and(Plunk::transactional())->toBeInstanceOf(Transactional::class)
        ->and(Plunk::events())->toBeInstanceOf(Events::class);
});

it('verifies an email address', function () {
    Http::fake([
        '*/v1/verify' => Http::response([
            'success' => true,
            'data' => [
                'email' => 'user@example.com',
                'valid' => true,
                'isDisposable' => false,
                'isAlias' => false,
                'isTypo' => false,
                'isPlusAddressed' => false,
                'isPersonalEmail' => false,
                'domainExists' => true,
                'hasWebsite' => false,
                'hasMxRecords' => true,
                'reasons' => ['Email appears to be valid'],
            ],
        ]),
    ]);

    $result = Plunk::verifyEmail('user@example.com');

    expect($result)->toBeInstanceOf(EmailVerification::class)
        ->and($result->valid)->toBeTrue()
        ->and($result->email)->toBe('user@example.com')
        ->and($result->isDisposable)->toBeFalse()
        ->and($result->hasMxRecords)->toBeTrue()
        ->and($result->domainExists)->toBeTrue()
        ->and($result->reasons)->toBe(['Email appears to be valid']);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/v1/verify')
            && $request['email'] === 'user@example.com';
    });
});

it('merges the package config', function () {
    expect(config('plunk.secret_key'))->toBe('sk_test_secret')
        ->and(config('plunk.base_api_url'))->toBe('https://next-api.useplunk.com')
        ->and(config('plunk.timeout'))->toBe(30);
});

it('registers the publishable config file', function () {
    $groups = ServiceProvider::publishableGroups();

    expect($groups)->toContain('plunk-config');

    $paths = ServiceProvider::pathsToPublish(null, 'plunk-config');

    expect($paths)->not->toBeEmpty();
    expect(array_values($paths)[0])->toEndWith('config/plunk.php');
});

it('throws when secret key is missing', function () {
    new PlunkManager(['secret_key' => null, 'base_api_url' => 'https://example.com']);
})->throws(AuthenticationException::class, 'PLUNK_SECRET_KEY');

it('throws when secret key is empty string', function () {
    new PlunkManager(['secret_key' => '', 'base_api_url' => 'https://example.com']);
})->throws(AuthenticationException::class, 'PLUNK_SECRET_KEY');
