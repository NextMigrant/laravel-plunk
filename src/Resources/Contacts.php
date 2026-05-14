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
     * List contacts with optional search and pagination.
     *
     * @param  string|null  $search  Filter by email substring.
     * @param  int  $limit  Items per page (max 100).
     * @param  string|null  $cursor  Cursor for the next page.
     * @return array<string, mixed>  Contains 'data' (Contact[]) and pagination info.
     */
    public function list(?string $search = null, int $limit = 20, ?string $cursor = null): array
    {
        $query = array_filter([
            'search' => $search,
            'limit' => $limit,
            'cursor' => $cursor,
        ], fn ($value) => ! is_null($value));

        $response = $this->client->get('/contacts', $query);

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
     * If the email already exists, the contact will be updated.
     *
     * @param  string  $email  The contact's email address.
     * @param  array<string, mixed>  $data  Custom data fields to associate.
     * @param  bool|null  $subscribed  Subscription state (defaults to false for new contacts).
     * @return array<string, mixed>  Contains the contact data and _meta with isNew/isUpdate flags.
     */
    public function create(string $email, array $data = [], ?bool $subscribed = null): array
    {
        $payload = array_filter([
            'email' => $email,
            'data' => $data ?: null,
            'subscribed' => $subscribed,
        ], fn ($value) => ! is_null($value));

        return $this->client->post('/contacts', $payload);
    }

    /**
     * Update a contact by ID.
     *
     * @param  string  $id  The contact ID.
     * @param  string|null  $email  New email address.
     * @param  bool|null  $subscribed  Subscription state.
     * @param  array<string, mixed>|null  $data  Custom data fields (set a key to null to remove it).
     * @return Contact
     */
    public function update(string $id, ?string $email = null, ?bool $subscribed = null, ?array $data = null): Contact
    {
        $payload = array_filter([
            'email' => $email,
            'subscribed' => $subscribed,
            'data' => $data,
        ], fn ($value) => ! is_null($value));

        $response = $this->client->patch("/contacts/{$id}", $payload);

        return Contact::fromArray($response);
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
     * Import contacts from a CSV file.
     *
     * The CSV should contain an "email" column. Maximum file size is 5MB.
     * This is an async operation — use bulkStatus() to poll for completion.
     *
     * @param  string  $csvPath  Absolute path to the CSV file.
     * @return array<string, mixed>  Contains the jobId for status polling.
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
     * Subscribe contacts in bulk (up to 1,000).
     *
     * @param  array<string>  $ids  Contact IDs to subscribe.
     * @return array<string, mixed>  Contains the jobId for status polling.
     */
    public function bulkSubscribe(array $ids): array
    {
        return $this->client->post('/contacts/bulk-subscribe', [
            'ids' => $ids,
        ]);
    }

    /**
     * Unsubscribe contacts in bulk (up to 1,000).
     *
     * @param  array<string>  $ids  Contact IDs to unsubscribe.
     * @return array<string, mixed>  Contains the jobId for status polling.
     */
    public function bulkUnsubscribe(array $ids): array
    {
        return $this->client->post('/contacts/bulk-unsubscribe', [
            'ids' => $ids,
        ]);
    }

    /**
     * Delete contacts in bulk (up to 1,000).
     *
     * @param  array<string>  $ids  Contact IDs to delete.
     * @return array<string, mixed>  Contains the jobId for status polling.
     */
    public function bulkDelete(array $ids): array
    {
        return $this->client->post('/contacts/bulk-delete', [
            'ids' => $ids,
        ]);
    }

    /**
     * Check the status of a bulk operation.
     *
     * @param  string  $jobId  The job ID returned from a bulk operation.
     * @return array<string, mixed>  Contains the job status and results.
     */
    public function bulkStatus(string $jobId): array
    {
        return $this->client->get("/contacts/bulk/{$jobId}");
    }
}
