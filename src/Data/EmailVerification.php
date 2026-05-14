<?php

namespace NextMigrant\Plunk\Data;

class EmailVerification
{
    public function __construct(
        public readonly string $email,
        public readonly bool $valid,
        public readonly bool $isDisposable,
        public readonly bool $isAlias,
        public readonly bool $isTypo,
        public readonly bool $isPlusAddressed,
        public readonly bool $isPersonalEmail,
        public readonly bool $domainExists,
        public readonly bool $hasWebsite,
        public readonly bool $hasMxRecords,
        public readonly array $reasons = [],
        public readonly ?string $suggestedEmail = null,
    ) {}

    /**
     * Create an EmailVerification instance from an API response array.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        $data = $attributes['data'] ?? $attributes;

        return new self(
            email: $data['email'] ?? '',
            valid: $data['valid'] ?? false,
            isDisposable: $data['isDisposable'] ?? false,
            isAlias: $data['isAlias'] ?? false,
            isTypo: $data['isTypo'] ?? false,
            isPlusAddressed: $data['isPlusAddressed'] ?? false,
            isPersonalEmail: $data['isPersonalEmail'] ?? false,
            domainExists: $data['domainExists'] ?? false,
            hasWebsite: $data['hasWebsite'] ?? false,
            hasMxRecords: $data['hasMxRecords'] ?? false,
            reasons: $data['reasons'] ?? [],
            suggestedEmail: $data['suggestedEmail'] ?? null,
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
            'email' => $this->email,
            'valid' => $this->valid,
            'isDisposable' => $this->isDisposable,
            'isAlias' => $this->isAlias,
            'isTypo' => $this->isTypo,
            'isPlusAddressed' => $this->isPlusAddressed,
            'isPersonalEmail' => $this->isPersonalEmail,
            'domainExists' => $this->domainExists,
            'hasWebsite' => $this->hasWebsite,
            'hasMxRecords' => $this->hasMxRecords,
            'reasons' => $this->reasons,
            'suggestedEmail' => $this->suggestedEmail,
        ];
    }
}
