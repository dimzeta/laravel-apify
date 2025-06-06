<?php

declare(strict_types=1);

namespace Apify\Laravel;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class ApifyClient
{
    protected Client $client;

    protected string $apiToken;

    protected string $baseUri = 'https://api.apify.com/v2/';

    public function __construct(string $apiToken, array $options = [])
    {
        $this->apiToken = $apiToken;

        $defaultOptions = [
            'base_uri' => $this->baseUri,
            'headers' => [
                'Authorization' => 'Bearer '.$apiToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ];

        $this->client = new Client(array_merge($defaultOptions, $options));
    }

    /**
     * Run an actor on the Apify platform.
     *
     * @throws ApifyException|GuzzleException
     */
    public function runActor(string $actorId, array $input = [], array $options = []): array
    {
        $waitForFinish = $options['waitForFinish'] ?? 60;
        $endpoint = "acts/{$actorId}/runs?waitForFinish={$waitForFinish}";

        $payload = $options;

        // merge payload with input
        if (! empty($input)) {
            $payload = array_merge($payload, $input);
        }

        try {
            $response = $this->client->post($endpoint, [
                'json' => $payload,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error('Apify API error: '.$e->getMessage());
            throw new ApifyException('Failed to run actor: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getDataset(string $datasetId, array $options = []): array
    {
        $endpoint = "datasets/{$datasetId}/items";

        $queryParams = array_filter([
            'format' => $options['format'] ?? 'json',
            'limit' => $options['limit'] ?? null,
            'offset' => $options['offset'] ?? null,
            'fields' => isset($options['fields']) ? implode(',', $options['fields']) : null,
        ]);

        try {
            $response = $this->client->get($endpoint, [
                'query' => $queryParams,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error('Apify API error: '.$e->getMessage());
            throw new ApifyException('Failed to get dataset: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getKeyValueStore(string $storeId, string $key): mixed
    {
        $endpoint = "key-value-stores/{$storeId}/records/{$key}";

        try {
            $response = $this->client->get($endpoint);
            $contentType = $response->getHeader('Content-Type')[0] ?? '';

            if (str_contains($contentType, 'application/json')) {
                return json_decode($response->getBody()->getContents(), true);
            }

            return $response->getBody()->getContents();
        } catch (RequestException $e) {
            Log::error('Apify API error: '.$e->getMessage());
            throw new ApifyException('Failed to get key-value store: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function setKeyValueStore(
        string $storeId,
        string $key,
        mixed $value,
        string $contentType = 'application/json'
    ): bool {
        $endpoint = "key-value-stores/{$storeId}/records/{$key}";

        $options = [
            'headers' => [
                'Content-Type' => $contentType,
            ],
        ];

        if ($contentType === 'application/json') {
            $options['json'] = $value;
        } else {
            $options['body'] = is_string($value) ? $value : json_encode($value);
        }

        try {
            $response = $this->client->put($endpoint, $options);

            return $response->getStatusCode() === 201;
        } catch (RequestException $e) {
            Log::error('Apify API error: '.$e->getMessage());
            throw new ApifyException('Failed to set key-value store: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getActorRun(string $runId): array
    {
        $endpoint = "actor-runs/{$runId}";

        try {
            $response = $this->client->get($endpoint);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error('Apify API error: '.$e->getMessage());
            throw new ApifyException('Failed to get actor run: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function abortActorRun(string $runId): array
    {
        $endpoint = "actor-runs/{$runId}/abort";

        try {
            $response = $this->client->post($endpoint);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error('Apify API error: '.$e->getMessage());
            throw new ApifyException('Failed to abort actor run: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getUser(): array
    {
        $endpoint = 'users/me';

        try {
            $response = $this->client->get($endpoint);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error('Apify API error: '.$e->getMessage());
            throw new ApifyException('Failed to get user info: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function listActors(array $options = []): array
    {
        $endpoint = 'acts';

        $queryParams = array_filter([
            'my' => $options['my'] ?? null,
            'limit' => $options['limit'] ?? null,
            'offset' => $options['offset'] ?? null,
        ]);

        try {
            $response = $this->client->get($endpoint, [
                'query' => $queryParams,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error('Apify API error: '.$e->getMessage());
            throw new ApifyException('Failed to list actors: '.$e->getMessage(), $e->getCode(), $e);
        }
    }
}
