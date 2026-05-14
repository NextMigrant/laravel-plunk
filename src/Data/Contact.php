<?php

namespace NextMigrant\Plunk\Data;

class Contact
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly bool $subscribed,
        public readonly array $data,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {}

    /**
     * Create a Contact instance from an API response array.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): static
    {
        return new static(
            id: $attributes['id'],
            email: $attributes['email'],
            subscribed: $attributes['subscribed'] ?? true,
            data: $attributes['data'] ?? [],
            createdAt: $attributes['createdAt'] ?? null,
            updatedAt: $attributes['updatedAt'] ?? null,
        );
    }

    /**
     * Convert the contact to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'subscribed' => $this->subscribed,
            'data' => $this->data,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
