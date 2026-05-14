<?php

namespace NextMigrant\Plunk\Resources;

use InvalidArgumentException;
use NextMigrant\Plunk\Data\Contact;
use NextMigrant\Plunk\PlunkClient;

class Contacts
{
    public function __construct(
        protected readonly PlunkClient $client,
    ) {}

    /**
     * List contacts with cursor-based pagination.
     *
     * @param  string|null  $search  Filter by email substring.
     * @param  int|null  $limit  Items per page (default 20, max 100).
     * @param  string|null  $cursor  Cursor from previous response.
     * @return array<string, mixed>  Returns { data: Contact[], cursor, hasMore, total }.
     */
    public function list(?string $search = null, ?int $limit = null, ?string $cursor = null): array
    {
        $query = array_filter([
            'search' => $search,
            'limit' => $limit,
            'cursor' => $cursor,
        ], fn ($value) => ! is_null($value));

        $response = $this->client->get('/contacts', $query);

        // Map data items to Contact DTOs if present.
        if (isset($response['data']) && is_array($response['data'])) {
            $response['data'] = array_map(
                fn (array $contact) => Contact::fromArray($contact),
                $response['data'],
            );
        }

        return $response;
    }

    /**
     * Get a single contact by ID.
     *
     * @return Contact
     */
    public function get(string $id): Contact
    {
        $response = $this->client->get("/contacts/{$id}");

        return Contact::fromArray($response);
    }

    /**
     * Create or upsert a contact by email.
     *
     * Returns `_meta.isNew` and `_meta.isUpdate` in the response.
     *
     * @param  string  $email  The contact's email address.
     * @param  bool|null  $subscribed  Whether the contact is subscribed (defaults to true).
     * @param  array<string, string|array<string>>|null  $data  Custom data fields.
     * @return array<string, mixed>
     */
    public function create(string $email, ?bool $subscribed = null, ?array $data = null): array
    {
        $payload = ['email' => $email];

        if ($subscribed !== null) {
            $payload['subscribed'] = $subscribed;
        }

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return $this->client->post('/contacts', $payload);
    }

    /**
     * Update a contact's email, subscription state, or data fields.
     *
     * @param  string  $id  The contact ID.
     * @param  string|null  $email  New email address.
     * @param  bool|null  $subscribed  Subscription state.
     * @param  array<string, string|array<string>|null>|null  $data  Custom data fields (set a key to null to remove it).
     * @return array<string, mixed>
     */
    public function update(string $id, ?string $email = null, ?bool $subscribed = null, ?array $data = null): array
    {
        $payload = array_filter([
            'email' => $email,
            'subscribed' => $subscribed,
            'data' => $data,
        ], fn ($value) => ! is_null($value));

        return $this->client->patch("/contacts/{$id}", $payload);
    }

    /**
     * Delete a contact by ID.
     *
     * @return array<string, mixed>
     */
    public function delete(string $id): array
    {
        return $this->client->delete("/contacts/{$id}");
    }

    /**
     * Bulk email-existence check (max 500 emails per call).
     *
     * @param  array<string>  $emails
     * @return array<string, mixed>
     */
    public function lookup(array $emails): array
    {
        return $this->client->post('/contacts/lookup', [
            'emails' => $emails,
        ]);
    }

    /**
     * Import contacts from a CSV file.
     *
     * The CSV should contain an "email" column. Maximum file size is 5MB.
     * Returns a jobId for status polling.
     *
     * @param  string  $csvPath  Absolute path to the CSV file.
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     */
    public function import(string $csvPath): array
    {
        if (! file_exists($csvPath) || ! is_readable($csvPath)) {
            throw new InvalidArgumentException("CSV file does not exist or is not readable: {$csvPath}");
        }

        $fileSize = filesize($csvPath);
        $maxSize = 5 * 1024 * 1024; // 5MB

        if ($fileSize > $maxSize) {
            throw new InvalidArgumentException(
                "CSV file exceeds the 5MB limit: " . round($fileSize / 1024 / 1024, 2) . "MB"
            );
        }

        return $this->client->post('/contacts/import', [
            'file' => base64_encode(file_get_contents($csvPath)),
        ]);
    }

    /**
     * Poll the status of a CSV import job.
     *
     * @return array<string, mixed>
     */
    public function importStatus(string $jobId): array
    {
        return $this->client->get("/contacts/import/{$jobId}");
    }

    /**
     * Subscribe up to 1,000 contacts by ID. Queued — returns a jobId.
     *
     * @param  array<string>  $ids
     * @return array<string, mixed>
     */
    public function bulkSubscribe(array $ids): array
    {
        return $this->client->post('/contacts/bulk-subscribe', [
            'ids' => $ids,
        ]);
    }

    /**
     * Unsubscribe up to 1,000 contacts by ID. Queued — returns a jobId.
     *
     * @param  array<string>  $ids
     * @return array<string, mixed>
     */
    public function bulkUnsubscribe(array $ids): array
    {
        return $this->client->post('/contacts/bulk-unsubscribe', [
            'ids' => $ids,
        ]);
    }

    /**
     * Delete up to 1,000 contacts by ID. Queued — returns a jobId.
     *
     * @param  array<string>  $ids
     * @return array<string, mixed>
     */
    public function bulkDelete(array $ids): array
    {
        return $this->client->post('/contacts/bulk-delete', [
            'ids' => $ids,
        ]);
    }

    /**
     * Poll the status of a bulk operation job.
     *
     * @return array<string, mixed>
     */
    public function bulkStatus(string $jobId): array
    {
        return $this->client->get("/contacts/bulk/{$jobId}");
    }
}
