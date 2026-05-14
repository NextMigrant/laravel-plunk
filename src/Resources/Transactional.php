<?php

namespace NextMigrant\Plunk\Resources;

use NextMigrant\Plunk\PlunkClient;

class Transactional
{
    public function __construct(
        protected readonly PlunkClient $client,
    ) {}

    /**
     * Send a transactional email.
     *
     * Requires either a `template` ID, or both `subject` and `body`.
     * When using a template, its subject/body/from/reply settings are used
     * unless explicitly overridden.
     *
     * @param  string|array<string|array{name: string, email: string}>  $to  Recipient(s). String, {name, email} object, or array of either. Max 5.
     * @param  string|null  $subject  Email subject (required if no template).
     * @param  string|null  $body  HTML body (required if no template).
     * @param  string|null  $template  Template ID to use.
     * @param  string|array{name: string, email: string}|null  $from  Sender email or {name, email} object (verified domain required).
     * @param  string|null  $name  Deprecated. Sender display name — prefer from: {name, email}.
     * @param  string|null  $reply  Reply-to email address.
     * @param  bool|null  $subscribed  Subscription state for the recipient contact.
     * @param  array<string, mixed>|null  $data  Template variables and contact data updates.
     * @param  array<string, string>  $headers  Custom email headers.
     * @param  array<array{filename: string, content: string, contentType: string, contentId?: string, disposition?: string}>  $attachments  Base64-encoded attachments (max 10, 10MB total).
     * @return array<string, mixed>
     */
    public function send(
        string|array $to,
        ?string $subject = null,
        ?string $body = null,
        ?string $template = null,
        string|array|null $from = null,
        ?string $name = null,
        ?string $reply = null,
        ?bool $subscribed = null,
        ?array $data = null,
        array $headers = [],
        array $attachments = [],
    ): array {
        $payload = array_filter([
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'template' => $template,
            'from' => $from,
            'name' => $name,
            'reply' => $reply,
            'subscribed' => $subscribed,
            'data' => $data,
            'headers' => $headers ?: null,
            'attachments' => $attachments ?: null,
        ], fn ($value) => ! is_null($value));

        return $this->client->post('/v1/send', $payload);
    }
}
