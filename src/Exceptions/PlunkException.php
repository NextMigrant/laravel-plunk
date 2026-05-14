<?php

namespace NextMigrant\Plunk\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

class PlunkException extends Exception
{
    public readonly ?Response $response;

    public readonly ?string $errorCode;

    public readonly ?string $requestId;

    public readonly ?string $suggestion;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Response $response = null,
        ?string $errorCode = null,
        ?string $requestId = null,
        ?string $suggestion = null,
        ?\Throwable $previous = null,
    ) {
        $this->response = $response;
        $this->errorCode = $errorCode;
        $this->requestId = $requestId;
        $this->suggestion = $suggestion;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create an exception from an HTTP response.
     *
     * Handles the standardized error format:
     * { success: false, error: { code, message, statusCode, requestId, errors, suggestion } }
     */
    public static function fromResponse(Response $response): self
    {
        $body = $response->json();
        $error = $body['error'] ?? null;

        // Handle nested error object (standardized format).
        if (is_array($error)) {
            $message = $error['message'] ?? 'Unknown Plunk API error';
            $errorCode = $error['code'] ?? null;
            $requestId = $error['requestId'] ?? null;
            $suggestion = $error['suggestion'] ?? null;
        } else {
            // Fallback for simple error responses.
            $message = $body['message'] ?? (is_string($error) ? $error : 'Unknown Plunk API error');
            $errorCode = null;
            $requestId = null;
            $suggestion = null;
        }

        return match ($response->status()) {
            401, 403 => new AuthenticationException($message, $response->status(), $response, $errorCode, $requestId, $suggestion),
            402 => new BillingException($message, $response->status(), $response, $errorCode, $requestId, $suggestion),
            409 => new ConflictException($message, $response->status(), $response, $errorCode, $requestId, $suggestion),
            422 => new ValidationException($message, $response->status(), $response, $errorCode, $requestId, $suggestion),
            429 => new RateLimitException($message, $response->status(), $response, $errorCode, $requestId, $suggestion),
            default => new self($message, $response->status(), $response, $errorCode, $requestId, $suggestion),
        };
    }
}
