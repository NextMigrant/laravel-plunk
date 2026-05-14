# Laravel Plunk

<p align="center">
    <a href="https://packagist.org/packages/nextmigrant/laravel-plunk"><img src="https://img.shields.io/packagist/v/nextmigrant/laravel-plunk.svg?style=flat-square" alt="Latest Version on Packagist"></a>
    <a href="https://github.com/nextmigrant/laravel-plunk/actions?query=workflow%3Arun-tests+branch%3Amain"><img src="https://img.shields.io/github/actions/workflow/status/nextmigrant/laravel-plunk/run-tests.yml?branch=main&label=tests&style=flat-square" alt="Tests"></a>
    <a href="https://github.com/nextmigrant/laravel-plunk/actions?query=workflow%3A%22Fix+PHP+code+style+issues%22+branch%3Amain"><img src="https://img.shields.io/github/actions/workflow/status/nextmigrant/laravel-plunk/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square" alt="Code Style"></a>
    <a href="https://packagist.org/packages/nextmigrant/laravel-plunk"><img src="https://img.shields.io/packagist/dt/nextmigrant/laravel-plunk.svg?style=flat-square" alt="Total Downloads"></a>
</p>

A clean, expressive Laravel package for the [Plunk](https://useplunk.com) email platform. Send transactional emails, manage contacts, track events, and verify email addresses — all through a simple Facade.

**Works with Plunk SaaS and self-hosted instances.**

---

## Features

- 📧 **Transactional Emails** — Send emails with templates, attachments, and custom headers
- 👥 **Contact Management** — Full CRUD with bulk subscribe/unsubscribe/delete and CSV import
- 📡 **Event Tracking** — Track events with automatic contact upsert and workflow triggers
- ✅ **Email Verification** — Validate format, MX records, disposable domains, and typos
- 🔑 **Dual Key Support** — Secret key for admin APIs, public key for event tracking
- 🛡️ **Typed Exceptions** — `AuthenticationException`, `ValidationException`, `RateLimitException`
- ⚡ **Built on Laravel HTTP Client** — Retries, timeouts, and `Http::fake()` for testing

## Requirements

- PHP 8.4+
- Laravel 11, 12, or 13

## Installation

```bash
composer require nextmigrant/laravel-plunk
```

Publish the config file:

```bash
php artisan vendor:publish --tag="laravel-plunk-config"
```

Add your configuration to `.env`:

```env
PLUNK_SECRET_KEY=sk_your_secret_key
PLUNK_PUBLIC_KEY=pk_your_public_key           # Required for event tracking
PLUNK_BASE_API_URL=https://next-api.useplunk.com  # Override for self-hosted instances
```

## Quick Start

```php
use NextMigrant\Plunk\Plunk;

// Send a transactional email
Plunk::transactional()->send(
    to: 'user@example.com',
    subject: 'Welcome aboard!',
    body: '<h1>Welcome to our platform</h1>',
);

// Track an event
Plunk::events()->track(
    email: 'user@example.com',
    event: 'signed_up',
    data: ['plan' => 'pro'],
);

// Verify an email
$result = Plunk::verifyEmail('user@example.com');
// $result->valid, $result->disposable, $result->typo, etc.
```

## Usage

### Transactional Emails

Send emails with the full range of Plunk options:

```php
Plunk::transactional()->send(
    to: 'user@example.com',           // string or array of emails
    subject: 'Your Invoice',
    body: '<h1>Invoice #1234</h1>',    // HTML body (or use template)
    from: 'billing@acme.com',          // Sender email (verified domain)
    name: 'Acme Inc',                  // Sender display name
    reply: 'billing@acme.com',          // Reply-to address
    cc: ['manager@acme.com'],          // CC recipients
    bcc: ['archive@acme.com'],         // BCC recipients
    headers: ['X-Priority' => '1'],    // Custom headers
    template: 'tpl_invoice',           // Use a Plunk template instead of body
    subscribed: true,                  // Add recipient to contacts
    data: ['invoice_id' => '1234'],    // Custom contact data
    attachments: [
        [
            'filename' => 'invoice.pdf',
            'content' => base64_encode($pdfContent),
        ],
    ],
);
```

### Event Tracking

Track events to trigger Plunk workflows. Contacts are created automatically if they don't exist:

```php
Plunk::events()->track(
    email: 'user@example.com',
    event: 'plan_upgraded',
    data: ['plan' => 'enterprise', 'seats' => 50],
);
```

> **Note:** The `/v1/track` endpoint requires a public key (`pk_*`). Secret keys are not accepted for this endpoint. Set `PLUNK_PUBLIC_KEY` in your `.env`.

### Contact Management

#### Basic CRUD

```php
// List contacts (paginated)
$result = Plunk::contacts()->list(
    search: 'john',    // Filter by email substring
    limit: 50,         // Items per page (max 100)
    cursor: $cursor,   // Cursor from previous response
);

foreach ($result['data'] as $contact) {
    echo $contact->email;       // Contact DTO
    echo $contact->subscribed;
}

// Get a single contact
$contact = Plunk::contacts()->get('contact_id');

// Create or upsert a contact
$result = Plunk::contacts()->create('new@example.com', [
    'source' => 'api',
    'plan' => 'free',
]);

// Update a contact
$contact = Plunk::contacts()->update('contact_id',
    email: 'updated@example.com',
    subscribed: false,
    data: ['plan' => 'pro'],
);

// Delete a contact
Plunk::contacts()->delete('contact_id');
```

#### Bulk Operations

All bulk operations are async and return a `jobId` for status polling:

```php
// Bulk subscribe/unsubscribe/delete (up to 1,000 IDs)
$result = Plunk::contacts()->bulkSubscribe(['id_1', 'id_2', 'id_3']);
$result = Plunk::contacts()->bulkUnsubscribe(['id_1', 'id_2']);
$result = Plunk::contacts()->bulkDelete(['id_1']);

// Poll job status
$status = Plunk::contacts()->bulkStatus($result['jobId']);
// $status['status'] => 'completed'

// Import from CSV (max 5MB)
$result = Plunk::contacts()->import('/path/to/contacts.csv');
```

### Email Verification

```php
$verification = Plunk::verifyEmail('user@example.com');

$verification->valid;            // bool — overall result
$verification->email;            // string — the email checked
$verification->isDisposable;     // bool — is a disposable domain
$verification->isAlias;          // bool — is an alias address
$verification->isTypo;           // bool — likely contains a typo
$verification->isPlusAddressed;  // bool — uses + addressing
$verification->isPersonalEmail;  // bool — personal vs business
$verification->domainExists;     // bool — domain resolves
$verification->hasWebsite;       // bool — domain has a website
$verification->hasMxRecords;     // bool — MX records exist
$verification->reasons;          // array — human-readable explanations
```

## Configuration

The published config file (`config/plunk.php`):

```php
return [
    'secret_key' => env('PLUNK_SECRET_KEY'),
    'public_key' => env('PLUNK_PUBLIC_KEY'),
    'base_api_url'   => env('PLUNK_BASE_API_URL', 'https://next-api.useplunk.com'),
    'timeout'    => env('PLUNK_TIMEOUT', 30),
    'retry'      => [
        'times' => 3,
        'sleep' => 100, // milliseconds
    ],
];
```

## Error Handling

The package throws typed exceptions mapped from HTTP status codes:

```php
use NextMigrant\Plunk\Exceptions\AuthenticationException;
use NextMigrant\Plunk\Exceptions\ValidationException;
use NextMigrant\Plunk\Exceptions\RateLimitException;
use NextMigrant\Plunk\Exceptions\PlunkException;

try {
    Plunk::transactional()->send(to: $email, subject: 'Hi', body: '<p>Hello</p>');
} catch (AuthenticationException $e) {
    // 401/403 — Invalid or missing API key
} catch (ValidationException $e) {
    // 422 — Invalid request payload
} catch (RateLimitException $e) {
    // 429 — Exceeded 1,000 requests/minute
} catch (PlunkException $e) {
    // Any other API error
    $e->response;  // Access the underlying HTTP response
}
```

## Testing

The package uses Laravel's HTTP client under the hood, so you can use `Http::fake()` in your application tests:

```php
use Illuminate\Support\Facades\Http;
use NextMigrant\Plunk\Plunk;

Http::fake([
    '*/v1/send' => Http::response(['success' => true]),
]);

Plunk::transactional()->send(
    to: 'user@example.com',
    subject: 'Test',
    body: '<p>Hello</p>',
);

Http::assertSent(fn ($request) =>
    str_contains($request->url(), '/v1/send')
    && $request['to'] === 'user@example.com'
);
```

Run the package test suite:

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [NextMigrant](https://github.com/nextmigrant)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
