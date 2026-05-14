<?php

namespace NextMigrant\Plunk;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use NextMigrant\Plunk\Exceptions\PlunkException;
use NextMigrant\Plunk\Exceptions\RateLimitException;

class PlunkClient
{
    public function __construct(
        protected readonly string $baseUrl,
        protected readonly string $apiKey,
        protected readonly int $timeout,
        protected readonly int $retryTimes,
        protected readonly int $retrySleep,
    ) {}

    /**
     * Send a GET request to the Plunk API.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     *
     * @throws PlunkException
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('get', $endpoint, query: $query);
    }

    /**
     * Send a POST request to the Plunk API.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws PlunkException
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('post', $endpoint, data: $data);
    }

    /**
     * Send a PATCH request to the Plunk API.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws PlunkException
     */
    public function patch(string $endpoint, array $data = []): array
    {
        return $this->request('patch', $endpoint, data: $data);
    }

    /**
     * Send a DELETE request to the Plunk API.
     *
     * @return array<string, mixed>
     *
     * @throws PlunkException
     */
    public function delete(string $endpoint): array
    {
        return $this->request('delete', $endpoint);
    }

    /**
     * Build the base pending request with auth, timeout, and retry configured.
     *
     * Retries use exponential backoff and only retry on 429 (rate limit)
     * or 5xx (server error) responses.
     */
    protected function buildRequest(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->apiKey)
            ->timeout($this->timeout)
            ->retry(
                times: $this->retryTimes,
                sleepMilliseconds: $this->retrySleep,
                when: fn (?\Exception $exception, PendingRequest $request) => $exception instanceof RateLimitException
                    || ($exception instanceof PlunkException && $exception->getCode() >= 500),
                throw: false,
            )
            ->acceptJson()
            ->asJson();
    }

    /**
     * Execute an HTTP request and handle the response.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     *
     * @throws PlunkException
     */
    protected function request(string $method, string $endpoint, array $data = [], array $query = []): array
    {
        $request = $this->buildRequest();

        /** @var Response $response */
        $response = match ($method) {
            'get' => $request->get($endpoint, $query),
            'post' => $request->post($endpoint, $data),
            'patch' => $request->patch($endpoint, $data),
            'delete' => $request->delete($endpoint),
        };

        if ($response->failed()) {
            throw PlunkException::fromResponse($response);
        }

        return $response->json() ?? [];
    }
}
