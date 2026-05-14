<?php

namespace NextMigrant\Plunk\Resources;

use NextMigrant\Plunk\PlunkClient;

class Events
{
    public function __construct(
        protected readonly PlunkClient $client,
    ) {}

    /**
     * Track an event for a contact.
     *
     * If the contact does not exist, it will be automatically created.
     * This endpoint can trigger workflows configured in the Plunk dashboard.
     *
     * @param  string  $email  The contact's email address.
     * @param  string  $event  The event name (e.g., "signed_up").
     * @param  array<string, mixed>  $data  Optional custom data to associate with the contact.
     * @return array<string, mixed>
     */
    public function track(string $email, string $event, array $data = []): array
    {
        $payload = array_filter([
            'email' => $email,
            'event' => $event,
            'data' => $data ?: null,
        ], fn ($value) => ! is_null($value));

        return $this->client->post('/v1/track', $payload);
    }
}
