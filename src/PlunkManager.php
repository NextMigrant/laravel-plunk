<?php

namespace NextMigrant\Plunk;

use NextMigrant\Plunk\Data\EmailVerification;
use NextMigrant\Plunk\Exceptions\AuthenticationException;
use NextMigrant\Plunk\Resources\Contacts;
use NextMigrant\Plunk\Resources\Events;
use NextMigrant\Plunk\Resources\Transactional;

class PlunkManager
{
    protected PlunkClient $client;

    protected ?PlunkClient $publicClient = null;

    protected ?Contacts $contacts = null;

    protected ?Transactional $transactional = null;

    protected ?Events $events = null;

    /**
     * @param  array<string, mixed>  $config
     *
     * @throws AuthenticationException
     */
    public function __construct(protected readonly array $config)
    {
        if (empty($this->config['secret_key'])) {
            throw new AuthenticationException(
                'Plunk secret key is not configured. Set PLUNK_SECRET_KEY in your .env file.'
            );
        }

        $this->client = new PlunkClient(
            baseUrl: $this->config['base_api_url'] ?? 'https://next-api.useplunk.com',
            apiKey: $this->config['secret_key'],
            timeout: $this->config['timeout'] ?? 30,
            retryTimes: $this->config['retry']['times'] ?? 3,
            retrySleep: $this->config['retry']['sleep'] ?? 100,
        );

        // Build a separate client for the public key if configured.
        // Used by the Events resource for the /v1/track endpoint.
        if (! empty($this->config['public_key'])) {
            $this->publicClient = new PlunkClient(
                baseUrl: $this->config['base_api_url'] ?? 'https://next-api.useplunk.com',
                apiKey: $this->config['public_key'],
                timeout: $this->config['timeout'] ?? 30,
                retryTimes: $this->config['retry']['times'] ?? 3,
                retrySleep: $this->config['retry']['sleep'] ?? 100,
            );
        }
    }

    /**
     * Access the Contacts resource for managing contacts.
     */
    public function contacts(): Contacts
    {
        return $this->contacts ??= new Contacts($this->client);
    }

    /**
     * Access the Transactional resource for sending emails.
     */
    public function transactional(): Transactional
    {
        return $this->transactional ??= new Transactional($this->client);
    }

    /**
     * Access the Events resource for tracking events.
     *
     * Requires a public key (pk_*) — the /v1/track endpoint does not accept secret keys.
     *
     * @throws AuthenticationException
     */
    public function events(): Events
    {
        if (! $this->publicClient) {
            throw new AuthenticationException(
                'Plunk public key is required for event tracking. Set PLUNK_PUBLIC_KEY in your .env file.'
            );
        }

        return $this->events ??= new Events($this->publicClient);
    }

    /**
     * Verify an email address.
     *
     * Checks format, MX records, disposable domains, and performs typo detection.
     */
    public function verifyEmail(string $email): EmailVerification
    {
        $response = $this->client->post('/v1/verify', [
            'email' => $email,
        ]);

        return EmailVerification::fromArray($response);
    }

    /**
     * Get the underlying HTTP client (for advanced usage).
     */
    public function getClient(): PlunkClient
    {
        return $this->client;
    }
}
