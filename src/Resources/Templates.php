<?php

namespace NextMigrant\Plunk\Resources;

use NextMigrant\Plunk\Data\Template;
use NextMigrant\Plunk\PlunkClient;

class Templates
{
    public function __construct(
        protected readonly PlunkClient $client,
    ) {}

    /**
     * List templates with pagination.
     *
     * @param  string|null  $search  Search by name.
     * @param  string|null  $type  Filter by type: "TRANSACTIONAL" or "MARKETING".
     * @param  int|null  $limit  Items per page.
     * @param  string|null  $cursor  Pagination cursor.
     * @return array<string, mixed>  Returns { templates: Template[], total, page, pageSize, totalPages }.
     */
    public function list(?string $search = null, ?string $type = null, ?int $limit = null, ?string $cursor = null): array
    {
        $query = array_filter([
            'search' => $search,
            'type' => $type,
            'limit' => $limit,
            'cursor' => $cursor,
        ], fn ($value) => ! is_null($value));

        $response = $this->client->get('/templates', $query);

        if (isset($response['templates']) && is_array($response['templates'])) {
            $response['templates'] = array_map(
                fn (array $template) => Template::fromArray($template),
                $response['templates'],
            );
        }

        return $response;
    }

    /**
     * Get a single template by ID.
     *
     * @return Template
     */
    public function get(string $id): Template
    {
        $response = $this->client->get("/templates/{$id}");

        return Template::fromArray($response);
    }

    /**
     * Create a new template.
     *
     * @param  string  $name  Template name.
     * @param  string  $subject  Email subject line.
     * @param  string  $body  HTML body.
     * @param  string  $type  "TRANSACTIONAL" or "MARKETING".
     * @return Template
     */
    public function create(string $name, string $subject, string $body, string $type = 'TRANSACTIONAL'): Template
    {
        $response = $this->client->post('/templates', [
            'name' => $name,
            'subject' => $subject,
            'body' => $body,
            'type' => $type,
        ]);

        return Template::fromArray($response);
    }

    /**
     * Update an existing template.
     *
     * @param  string  $id  Template ID.
     * @param  string|null  $name  Template name.
     * @param  string|null  $subject  Email subject line.
     * @param  string|null  $body  HTML body.
     * @param  string|null  $type  "TRANSACTIONAL" or "MARKETING".
     * @return Template
     */
    public function update(string $id, ?string $name = null, ?string $subject = null, ?string $body = null, ?string $type = null): Template
    {
        $payload = array_filter([
            'name' => $name,
            'subject' => $subject,
            'body' => $body,
            'type' => $type,
        ], fn ($value) => ! is_null($value));

        $response = $this->client->patch("/templates/{$id}", $payload);

        return Template::fromArray($response);
    }

    /**
     * Delete a template by ID.
     *
     * @return array<string, mixed>
     */
    public function delete(string $id): array
    {
        return $this->client->delete("/templates/{$id}");
    }

    /**
     * Duplicate a template. Returns the new template.
     *
     * @return Template
     */
    public function duplicate(string $id): Template
    {
        $response = $this->client->post("/templates/{$id}/duplicate");

        return Template::fromArray($response);
    }

    /**
     * List campaigns and workflow steps that reference this template.
     *
     * @return array<string, mixed>
     */
    public function usage(string $id): array
    {
        return $this->client->get("/templates/{$id}/usage");
    }
}
