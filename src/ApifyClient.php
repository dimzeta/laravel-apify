<?php

declare(strict_types=1);

namespace Apify\Laravel;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

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
     * @param  string  $actorId  The ID or name of the actor (e.g., "apify/web-scraper")
     * @param  array<string, mixed>  $input  The input data for the actor
     * @param  array{
     *     waitForFinish?: int,
     *     timeout?: int,
     *     memory?: int,
     *     build?: string,
     *     webhooks?: array<array{eventTypes: string[], requestUrl: string}>,
     *     maxItems?: int,
     *     maxTotalChargeUsd?: float
     * }  $options  Query parameters for the API call
     *
     * @throws ApifyException|GuzzleException
     */
    public function runActor(string $actorId, array $input = [], array $options = []): array
    {
        $queryParams = $this->buildRunActorQueryParams($options);
        $endpoint = "acts/{$actorId}/runs?".http_build_query($queryParams);

        try {
            $response = $this->client->post($endpoint, [
                'json' => $input,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw new ApifyException('Failed to run actor: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Run an actor synchronously and return its OUTPUT from the key-value store.
     *
     * This endpoint waits for the actor to finish and returns the OUTPUT record
     * from the actor's default key-value store. Use this for actors that complete
     * in under 5 minutes. If the run exceeds 300 seconds, a 408 timeout error will be thrown.
     *
     * @param  string  $actorId  The ID or name of the actor (e.g., "apify/web-scraper")
     * @param  array<string, mixed>  $input  The input data for the actor
     * @param  array{
     *     timeout?: int,
     *     memory?: int,
     *     build?: string,
     *     webhooks?: array<array{eventTypes: string[], requestUrl: string}>,
     *     maxItems?: int,
     *     maxTotalChargeUsd?: float
     * }  $options  Query parameters for the API call
     * @return mixed The OUTPUT record from the actor's default key-value store
     *
     * @throws ApifyException|GuzzleException
     */
    public function runActorSync(string $actorId, array $input = [], array $options = []): mixed
    {
        $queryParams = $this->buildRunActorQueryParams($options, false);
        $endpoint = "acts/{$actorId}/run-sync";

        if (! empty($queryParams)) {
            $endpoint .= '?'.http_build_query($queryParams);
        }

        try {
            $response = $this->client->post($endpoint, [
                'json' => $input,
            ]);

            $contentType = $response->getHeader('Content-Type')[0] ?? '';

            if (str_contains($contentType, 'application/json')) {
                return json_decode($response->getBody()->getContents(), true);
            }

            return $response->getBody()->getContents();
        } catch (RequestException $e) {
            throw new ApifyException('Failed to run actor synchronously: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Run an actor synchronously and return its dataset items.
     *
     * This endpoint waits for the actor to finish and returns the items from
     * the actor's default dataset. Use this for actors that complete in under
     * 5 minutes and store results in a dataset. If the run exceeds 300 seconds,
     * a 408 timeout error will be thrown.
     *
     * @param  string  $actorId  The ID or name of the actor (e.g., "apify/web-scraper")
     * @param  array<string, mixed>  $input  The input data for the actor
     * @param  array{
     *     timeout?: int,
     *     memory?: int,
     *     build?: string,
     *     webhooks?: array<array{eventTypes: string[], requestUrl: string}>,
     *     maxItems?: int,
     *     maxTotalChargeUsd?: float,
     *     format?: string,
     *     fields?: string[],
     *     limit?: int,
     *     offset?: int
     * }  $options  Query parameters for the API call
     * @return array<mixed> The dataset items
     *
     * @throws ApifyException|GuzzleException
     */
    public function runActorSyncDataset(string $actorId, array $input = [], array $options = []): array
    {
        $queryParams = $this->buildRunActorQueryParams($options, false);

        // Add dataset-specific options
        if (isset($options['format'])) {
            $queryParams['format'] = $options['format'];
        }
        if (isset($options['fields'])) {
            $queryParams['fields'] = implode(',', $options['fields']);
        }
        if (isset($options['limit'])) {
            $queryParams['limit'] = $options['limit'];
        }
        if (isset($options['offset'])) {
            $queryParams['offset'] = $options['offset'];
        }

        $endpoint = "acts/{$actorId}/run-sync-get-dataset-items";

        if (! empty($queryParams)) {
            $endpoint .= '?'.http_build_query($queryParams);
        }

        try {
            $response = $this->client->post($endpoint, [
                'json' => $input,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw new ApifyException('Failed to run actor synchronously: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Build query parameters for the run actor endpoint.
     *
     * @param  array<string, mixed>  $options  Raw options
     * @param  bool  $includeWaitForFinish  Whether to include waitForFinish param (not used for sync endpoints)
     * @return array<string, mixed> Filtered and formatted query params
     */
    private function buildRunActorQueryParams(array $options, bool $includeWaitForFinish = true): array
    {
        $supported = ['waitForFinish', 'timeout', 'memory', 'build', 'webhooks', 'maxItems', 'maxTotalChargeUsd'];
        $params = [];

        foreach ($supported as $key) {
            if (! isset($options[$key])) {
                continue;
            }

            if ($key === 'waitForFinish' && ! $includeWaitForFinish) {
                continue;
            }

            $value = $options[$key];

            if ($key === 'webhooks' && is_array($value)) {
                $value = base64_encode(json_encode($value));
            }

            $params[$key] = $value;
        }

        if ($includeWaitForFinish) {
            $params['waitForFinish'] ??= 60;
        }

        return $params;
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
            throw new ApifyException('Failed to list actors: '.$e->getMessage(), $e->getCode(), $e);
        }
    }
}
