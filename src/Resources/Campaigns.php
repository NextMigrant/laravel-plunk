<?php

namespace NextMigrant\Plunk\Resources;

use NextMigrant\Plunk\PlunkClient;

class Campaigns
{
    public function __construct(
        protected readonly PlunkClient $client,
    ) {}

    /**
     * List all campaigns.
     *
     * @return array<string, mixed>
     */
    public function list(): array
    {
        return $this->client->get('/campaigns');
    }

    /**
     * Get a single campaign by ID.
     *
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->client->get("/campaigns/{$id}");
    }

    /**
     * Create a new campaign in DRAFT status.
     *
     * @param  string  $name  Campaign name.
     * @param  string  $subject  Email subject line.
     * @param  string  $body  HTML email body.
     * @param  string  $from  Sender email (verified domain required).
     * @param  string  $audienceType  "ALL", "SEGMENT", or "FILTERED".
     * @param  string|null  $description  Campaign description.
     * @param  string|null  $fromName  Sender display name.
     * @param  string|null  $replyTo  Reply-to email address.
     * @param  string|null  $segmentId  Segment ID (required if audienceType is "SEGMENT").
     * @param  array<string, mixed>|null  $audienceFilter  Filter conditions (required if audienceType is "FILTERED").
     * @return array<string, mixed>
     */
    public function create(
        string $name,
        string $subject,
        string $body,
        string $from,
        string $audienceType = 'ALL',
        ?string $description = null,
        ?string $fromName = null,
        ?string $replyTo = null,
        ?string $segmentId = null,
        ?array $audienceFilter = null,
    ): array {
        $payload = array_filter([
            'name' => $name,
            'subject' => $subject,
            'body' => $body,
            'from' => $from,
            'audienceType' => $audienceType,
            'description' => $description,
            'fromName' => $fromName,
            'replyTo' => $replyTo,
            'segmentId' => $segmentId,
            'audienceFilter' => $audienceFilter,
        ], fn ($value) => ! is_null($value));

        return $this->client->post('/campaigns', $payload);
    }

    /**
     * Update a campaign (full replace).
     *
     * @param  string  $id  Campaign ID.
     * @param  array<string, mixed>  $data  Full campaign data.
     * @return array<string, mixed>
     */
    public function update(string $id, array $data): array
    {
        return $this->client->put("/campaigns/{$id}", $data);
    }

    /**
     * Delete a campaign. Returns 409 if it has active executions.
     *
     * @return array<string, mixed>
     */
    public function delete(string $id): array
    {
        return $this->client->delete("/campaigns/{$id}");
    }

    /**
     * Duplicate a campaign. Returns the new campaign in DRAFT status.
     *
     * @return array<string, mixed>
     */
    public function duplicate(string $id): array
    {
        return $this->client->post("/campaigns/{$id}/duplicate");
    }

    /**
     * Send or schedule a campaign.
     *
     * @param  string  $id  Campaign ID.
     * @param  string|null  $scheduledFor  ISO 8601 datetime for delayed sends.
     * @return array<string, mixed>
     */
    public function send(string $id, ?string $scheduledFor = null): array
    {
        $payload = array_filter([
            'scheduledFor' => $scheduledFor,
        ], fn ($value) => ! is_null($value));

        return $this->client->post("/campaigns/{$id}/send", $payload);
    }

    /**
     * Cancel a SCHEDULED or SENDING campaign.
     *
     * @return array<string, mixed>
     */
    public function cancel(string $id): array
    {
        return $this->client->post("/campaigns/{$id}/cancel");
    }

    /**
     * Send a test email to a single address.
     *
     * @return array<string, mixed>
     */
    public function test(string $id, string $email): array
    {
        return $this->client->post("/campaigns/{$id}/test", [
            'email' => $email,
        ]);
    }

    /**
     * Get campaign statistics (send/open/click/bounce counts).
     *
     * @return array<string, mixed>
     */
    public function stats(string $id): array
    {
        return $this->client->get("/campaigns/{$id}/stats");
    }
}
