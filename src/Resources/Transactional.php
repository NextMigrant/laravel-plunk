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
     * @param  string|array<string>  $to  Recipient email address(es).
     * @param  string  $subject  Email subject line.
     * @param  string|null  $body  HTML or plain-text body (omit if using a template).
     * @param  string|null  $from  Sender email address (must be a verified domain).
     * @param  string|null  $name  Sender display name.
     * @param  string|null  $reply  Reply-to email address.
     * @param  array<string>  $cc  CC recipient email addresses.
     * @param  array<string>  $bcc  BCC recipient email addresses.
     * @param  array<string, string>  $headers  Custom email headers.
     * @param  array<array{filename: string, content: string}>  $attachments  Base64-encoded attachments.
     * @param  string|null  $template  Plunk template ID.
     * @param  bool|null  $subscribed  Whether to add recipient to contacts.
     * @param  array<string, mixed>  $data  Custom data to associate with the contact.
     * @return array<string, mixed>
     */
    public function send(
        string|array $to,
        string $subject,
        ?string $body = null,
        ?string $from = null,
        ?string $name = null,
        ?string $reply = null,
        array $cc = [],
        array $bcc = [],
        array $headers = [],
        array $attachments = [],
        ?string $template = null,
        ?bool $subscribed = null,
        array $data = [],
    ): array {
        $payload = array_filter([
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'from' => $from,
            'name' => $name,
            'reply' => $reply,
            'cc' => $cc ?: null,
            'bcc' => $bcc ?: null,
            'headers' => $headers ?: null,
            'attachments' => $attachments ?: null,
            'template' => $template,
            'subscribed' => $subscribed,
            'data' => $data ?: null,
        ], fn ($value) => ! is_null($value));

        return $this->client->post('/v1/send', $payload);
    }
}
