<?php

namespace NextMigrant\Plunk\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

class PlunkException extends Exception
{
    public readonly ?Response $response;

    public function __construct(string $message = '', int $code = 0, ?Response $response = null, ?\Throwable $previous = null)
    {
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create an exception from an HTTP response.
     */
    public static function fromResponse(Response $response): static
    {
        $body = $response->json();
        $message = $body['message'] ?? $body['error'] ?? 'Unknown Plunk API error';

        return match ($response->status()) {
            401, 403 => new AuthenticationException($message, $response->status(), $response),
            422 => new ValidationException($message, $response->status(), $response),
            429 => new RateLimitException($message, $response->status(), $response),
            default => new static($message, $response->status(), $response),
        };
    }
}
