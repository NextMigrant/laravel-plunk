<?php

namespace NextMigrant\Plunk\Resources;

use NextMigrant\Plunk\PlunkClient;

class Segments
{
    public function __construct(
        protected readonly PlunkClient $client,
    ) {}

    /**
     * List all segments (no pagination — small list).
     *
     * @return array<string, mixed>
     */
    public function list(): array
    {
        return $this->client->get('/segments');
    }

    /**
     * Get a segment, including cached memberCount.
     *
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->client->get("/segments/{$id}");
    }

    /**
     * Create a new segment.
     *
     * @param  string  $name  Segment name.
     * @param  array<string, mixed>|null  $filters  Filter conditions.
     * @param  bool|null  $trackMembership  Whether to track entry/exit events.
     * @return array<string, mixed>
     */
    public function create(string $name, ?array $filters = null, ?bool $trackMembership = null): array
    {
        $payload = array_filter([
            'name' => $name,
            'filters' => $filters,
            'trackMembership' => $trackMembership,
        ], fn ($value) => ! is_null($value));

        return $this->client->post('/segments', $payload);
    }

    /**
     * Update a segment's name, description, condition, or trackMembership.
     *
     * @param  string  $id  Segment ID.
     * @param  array<string, mixed>  $data  Fields to update.
     * @return array<string, mixed>
     */
    public function update(string $id, array $data): array
    {
        return $this->client->patch("/segments/{$id}", $data);
    }

    /**
     * Delete a segment. Returns 409 if used by an active campaign.
     *
     * @return array<string, mixed>
     */
    public function delete(string $id): array
    {
        return $this->client->delete("/segments/{$id}");
    }

    /**
     * List segment members (page-based pagination).
     *
     * @param  string  $id  Segment ID.
     * @param  int|null  $page  Page number.
     * @param  int|null  $pageSize  Items per page (max 100).
     * @return array<string, mixed>
     */
    public function contacts(string $id, ?int $page = null, ?int $pageSize = null): array
    {
        $query = array_filter([
            'page' => $page,
            'pageSize' => $pageSize,
        ], fn ($value) => ! is_null($value));

        return $this->client->get("/segments/{$id}/contacts", $query);
    }

    /**
     * Add emails to a static segment.
     *
     * @param  string  $id  Segment ID.
     * @param  array<string>  $emails  Email addresses to add.
     * @param  bool|null  $createMissing  Create contacts that don't exist.
     * @param  bool|null  $subscribed  Subscription state for newly created contacts.
     * @return array<string, mixed>
     */
    public function addMembers(string $id, array $emails, ?bool $createMissing = null, ?bool $subscribed = null): array
    {
        $payload = array_filter([
            'emails' => $emails,
            'createMissing' => $createMissing,
            'subscribed' => $subscribed,
        ], fn ($value) => ! is_null($value));

        return $this->client->post("/segments/{$id}/members", $payload);
    }

    /**
     * Remove emails from a static segment.
     *
     * @param  string  $id  Segment ID.
     * @param  array<string>  $emails  Email addresses to remove.
     * @return array<string, mixed>
     */
    public function removeMembers(string $id, array $emails): array
    {
        return $this->client->delete("/segments/{$id}/members", [
            'emails' => $emails,
        ]);
    }

    /**
     * Recompute membership for a tracked dynamic segment — fires entry/exit events.
     *
     * @return array<string, mixed>
     */
    public function compute(string $id): array
    {
        return $this->client->post("/segments/{$id}/compute");
    }

    /**
     * Cheap count refresh — no events, no membership writes.
     *
     * @return array<string, mixed>
     */
    public function refresh(string $id): array
    {
        return $this->client->post("/segments/{$id}/refresh");
    }
}
