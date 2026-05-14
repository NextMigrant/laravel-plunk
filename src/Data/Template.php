<?php

namespace NextMigrant\Plunk\Data;

class Template
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $subject,
        public readonly string $body,
        public readonly string $type,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {}

    /**
     * Create a Template instance from an API response array.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            id: $attributes['id'],
            name: $attributes['name'],
            subject: $attributes['subject'],
            body: $attributes['body'],
            type: $attributes['type'],
            createdAt: $attributes['createdAt'] ?? null,
            updatedAt: $attributes['updatedAt'] ?? null,
        );
    }

    /**
     * Convert to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'subject' => $this->subject,
            'body' => $this->body,
            'type' => $this->type,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
