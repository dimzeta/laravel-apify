<?php

use Apify\Laravel\ApifyClient;
use Apify\Laravel\ApifyException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

beforeEach(function () {
    $this->apiToken = 'test-api-token';
    $this->mockHandler = new MockHandler;
    $this->handlerStack = HandlerStack::create($this->mockHandler);

    $this->apifyClient = new ApifyClient($this->apiToken, [
        'handler' => $this->handlerStack,
    ]);
});

describe('ApifyClient Construction', function () {
    it('can be instantiated with api token', function () {
        $client = new ApifyClient('test-token');

        expect($client)->toBeInstanceOf(ApifyClient::class);
    });

    it('sets correct headers and base uri', function () {
        $client = new ApifyClient('test-token');

        expect($client)->toBeInstanceOf(ApifyClient::class);
    });
});

describe('runActor', function () {
    it('can run an actor successfully', function () {
        $this->mockHandler->append(new Response(200, [], json_encode([
            'data' => [
                'id' => 'run-123',
                'status' => 'SUCCEEDED',
                'startedAt' => '2024-01-01T00:00:00.000Z',
                'finishedAt' => '2024-01-01T00:01:00.000Z',
            ],
        ])));

        $result = $this->apifyClient->runActor('test-actor', ['url' => 'https://example.com']);

        expect($result)
            ->toBeArray()
            ->toHaveKey('data')
            ->and($result['data'])
            ->toHaveKey('id', 'run-123')
            ->toHaveKey('status', 'SUCCEEDED');
    });

    it('can run an actor with custom options', function () {
        $this->mockHandler->append(new Response(200, [], json_encode([
            'data' => ['id' => 'run-123'],
        ])));

        $result = $this->apifyClient->runActor('test-actor', ['url' => 'https://example.com'], [
            'waitForFinish' => 120,
            'memory' => 1024,
        ]);

        expect($result)->toBeArray();
    });

    it('throws exception on api error', function () {
        $this->mockHandler->append(new RequestException(
            'API Error',
            new Request('POST', 'acts/test-actor/runs'),
            new Response(400, [], json_encode(['error' => 'Invalid input']))
        ));

        expect(fn () => $this->apifyClient->runActor('invalid-actor'))
            ->toThrow(ApifyException::class, 'Failed to run actor');
    });

    it('sends input as JSON body only without options', function () {
        $this->mockHandler->append(new Response(200, [], json_encode([
            'data' => ['id' => 'run-123'],
        ])));

        $input = ['url' => 'https://example.com', 'maxPages' => 10];
        $options = ['waitForFinish' => 120, 'memory' => 1024];

        $this->apifyClient->runActor('test-actor', $input, $options);

        $lastRequest = $this->mockHandler->getLastRequest();
        $body = json_decode($lastRequest->getBody()->getContents(), true);

        expect($body)->toBe($input);
        expect($body)->not->toHaveKey('waitForFinish');
        expect($body)->not->toHaveKey('memory');
    });

    it('includes all supported options as query parameters', function () {
        $this->mockHandler->append(new Response(200, [], json_encode([
            'data' => ['id' => 'run-123'],
        ])));

        $options = [
            'waitForFinish' => 120,
            'timeout' => 300,
            'memory' => 2048,
            'build' => 'latest',
            'maxItems' => 1000,
            'maxTotalChargeUsd' => 5.0,
        ];

        $this->apifyClient->runActor('test-actor', [], $options);

        $lastRequest = $this->mockHandler->getLastRequest();
        $uri = $lastRequest->getUri();

        parse_str($uri->getQuery(), $queryParams);

        expect($queryParams['waitForFinish'])->toBe('120');
        expect($queryParams['timeout'])->toBe('300');
        expect($queryParams['memory'])->toBe('2048');
        expect($queryParams['build'])->toBe('latest');
        expect($queryParams['maxItems'])->toBe('1000');
        expect($queryParams['maxTotalChargeUsd'])->toBe('5');
    });

    it('encodes webhooks as base64 JSON in query parameter', function () {
        $this->mockHandler->append(new Response(200, [], json_encode([
            'data' => ['id' => 'run-123'],
        ])));

        $webhooks = [
            [
                'eventTypes' => ['ACTOR.RUN.SUCCEEDED'],
                'requestUrl' => 'https://myapp.com/webhook',
            ],
        ];

        $this->apifyClient->runActor('test-actor', [], ['webhooks' => $webhooks]);

        $lastRequest = $this->mockHandler->getLastRequest();
        parse_str($lastRequest->getUri()->getQuery(), $queryParams);

        $decodedWebhooks = json_decode(base64_decode($queryParams['webhooks']), true);

        expect($decodedWebhooks)->toBe($webhooks);
    });

    it('uses default waitForFinish of 60 when not specified', function () {
        $this->mockHandler->append(new Response(200, [], json_encode([
            'data' => ['id' => 'run-123'],
        ])));

        $this->apifyClient->runActor('test-actor', ['url' => 'https://example.com']);

        $lastRequest = $this->mockHandler->getLastRequest();
        parse_str($lastRequest->getUri()->getQuery(), $queryParams);

        expect($queryParams['waitForFinish'])->toBe('60');
    });

    it('does not include unknown options in query parameters', function () {
        $this->mockHandler->append(new Response(200, [], json_encode([
            'data' => ['id' => 'run-123'],
        ])));

        $options = [
            'waitForFinish' => 60,
            'unknownOption' => 'value',
            'anotherUnknown' => 123,
        ];

        $this->apifyClient->runActor('test-actor', [], $options);

        $lastRequest = $this->mockHandler->getLastRequest();
        parse_str($lastRequest->getUri()->getQuery(), $queryParams);

        expect($queryParams)->not->toHaveKey('unknownOption');
        expect($queryParams)->not->toHaveKey('anotherUnknown');
    });
});

describe('runActorSync', function () {
    it('can run an actor synchronously and return JSON output', function () {
        $output = ['result' => 'data', 'items' => [1, 2, 3]];

        $this->mockHandler->append(new Response(200, [
            'Content-Type' => 'application/json',
        ], json_encode($output)));

        $result = $this->apifyClient->runActorSync('test-actor', ['url' => 'https://example.com']);

        expect($result)
            ->toBeArray()
            ->toHaveKey('result', 'data')
            ->toHaveKey('items');
    });

    it('can return non-JSON output', function () {
        $htmlOutput = '<html><body>Result</body></html>';

        $this->mockHandler->append(new Response(200, [
            'Content-Type' => 'text/html',
        ], $htmlOutput));

        $result = $this->apifyClient->runActorSync('test-actor', ['url' => 'https://example.com']);

        expect($result)->toBe($htmlOutput);
    });

    it('sends input as JSON body', function () {
        $this->mockHandler->append(new Response(200, [
            'Content-Type' => 'application/json',
        ], json_encode(['result' => 'ok'])));

        $input = ['url' => 'https://example.com', 'maxPages' => 10];

        $this->apifyClient->runActorSync('test-actor', $input);

        $lastRequest = $this->mockHandler->getLastRequest();
        $body = json_decode($lastRequest->getBody()->getContents(), true);

        expect($body)->toBe($input);
    });

    it('calls the run-sync endpoint', function () {
        $this->mockHandler->append(new Response(200, [
            'Content-Type' => 'application/json',
        ], json_encode(['result' => 'ok'])));

        $this->apifyClient->runActorSync('my-actor', []);

        $lastRequest = $this->mockHandler->getLastRequest();

        expect($lastRequest->getUri()->getPath())->toBe('/v2/acts/my-actor/run-sync');
    });

    it('does not include waitForFinish in query params', function () {
        $this->mockHandler->append(new Response(200, [
            'Content-Type' => 'application/json',
        ], json_encode(['result' => 'ok'])));

        $this->apifyClient->runActorSync('test-actor', [], ['memory' => 1024]);

        $lastRequest = $this->mockHandler->getLastRequest();
        $query = $lastRequest->getUri()->getQuery();

        expect($query)->not->toContain('waitForFinish');
        expect($query)->toContain('memory=1024');
    });

    it('supports memory, build, and other options', function () {
        $this->mockHandler->append(new Response(200, [
            'Content-Type' => 'application/json',
        ], json_encode(['result' => 'ok'])));

        $options = [
            'memory' => 2048,
            'build' => 'latest',
            'maxItems' => 500,
        ];

        $this->apifyClient->runActorSync('test-actor', [], $options);

        $lastRequest = $this->mockHandler->getLastRequest();
        parse_str($lastRequest->getUri()->getQuery(), $queryParams);

        expect($queryParams['memory'])->toBe('2048');
        expect($queryParams['build'])->toBe('latest');
        expect($queryParams['maxItems'])->toBe('500');
    });

    it('throws exception on api error', function () {
        $this->mockHandler->append(new RequestException(
            'Timeout',
            new Request('POST', 'acts/test-actor/run-sync'),
            new Response(408, [], json_encode(['error' => 'Request timeout']))
        ));

        expect(fn () => $this->apifyClient->runActorSync('test-actor'))
            ->toThrow(ApifyException::class, 'Failed to run actor synchronously');
    });
});

describe('runActorSyncDataset', function () {
    it('can run an actor and return dataset items', function () {
        $items = [
            ['title' => 'Item 1', 'price' => 10],
            ['title' => 'Item 2', 'price' => 20],
        ];

        $this->mockHandler->append(new Response(200, [], json_encode($items)));

        $result = $this->apifyClient->runActorSyncDataset('test-actor', ['url' => 'https://example.com']);

        expect($result)
            ->toBeArray()
            ->toHaveCount(2)
            ->and($result[0])
            ->toHaveKey('title', 'Item 1');
    });

    it('calls the run-sync-get-dataset-items endpoint', function () {
        $this->mockHandler->append(new Response(200, [], json_encode([])));

        $this->apifyClient->runActorSyncDataset('my-actor', []);

        $lastRequest = $this->mockHandler->getLastRequest();

        expect($lastRequest->getUri()->getPath())->toBe('/v2/acts/my-actor/run-sync-get-dataset-items');
    });

    it('supports format option', function () {
        $this->mockHandler->append(new Response(200, [], json_encode([])));

        $this->apifyClient->runActorSyncDataset('test-actor', [], ['format' => 'csv']);

        $lastRequest = $this->mockHandler->getLastRequest();
        parse_str($lastRequest->getUri()->getQuery(), $queryParams);

        expect($queryParams['format'])->toBe('csv');
    });

    it('supports fields option and joins them with comma', function () {
        $this->mockHandler->append(new Response(200, [], json_encode([])));

        $this->apifyClient->runActorSyncDataset('test-actor', [], ['fields' => ['title', 'price', 'url']]);

        $lastRequest = $this->mockHandler->getLastRequest();
        parse_str($lastRequest->getUri()->getQuery(), $queryParams);

        expect($queryParams['fields'])->toBe('title,price,url');
    });

    it('supports pagination options', function () {
        $this->mockHandler->append(new Response(200, [], json_encode([])));

        $this->apifyClient->runActorSyncDataset('test-actor', [], [
            'limit' => 50,
            'offset' => 100,
        ]);

        $lastRequest = $this->mockHandler->getLastRequest();
        parse_str($lastRequest->getUri()->getQuery(), $queryParams);

        expect($queryParams['limit'])->toBe('50');
        expect($queryParams['offset'])->toBe('100');
    });

    it('does not include waitForFinish in query params', function () {
        $this->mockHandler->append(new Response(200, [], json_encode([])));

        $this->apifyClient->runActorSyncDataset('test-actor', [], ['memory' => 1024]);

        $lastRequest = $this->mockHandler->getLastRequest();
        $query = $lastRequest->getUri()->getQuery();

        expect($query)->not->toContain('waitForFinish');
    });

    it('throws exception on api error', function () {
        $this->mockHandler->append(new RequestException(
            'Timeout',
            new Request('POST', 'acts/test-actor/run-sync-get-dataset-items'),
            new Response(408, [], json_encode(['error' => 'Request timeout']))
        ));

        expect(fn () => $this->apifyClient->runActorSyncDataset('test-actor'))
            ->toThrow(ApifyException::class, 'Failed to run actor synchronously');
    });
});

describe('getDataset', function () {
    it('can retrieve dataset items', function () {
        $mockData = [
            ['title' => 'Item 1', 'url' => 'https://example.com/1'],
            ['title' => 'Item 2', 'url' => 'https://example.com/2'],
        ];

        $this->mockHandler->append(new Response(200, [], json_encode($mockData)));

        $result = $this->apifyClient->getDataset('dataset-123');

        expect($result)
            ->toBeArray()
            ->toHaveCount(2)
            ->and($result[0])
            ->toHaveKey('title', 'Item 1')
            ->toHaveKey('url', 'https://example.com/1');
    });

    it('can retrieve dataset with pagination options', function () {
        $this->mockHandler->append(new Response(200, [], json_encode([
            ['title' => 'Item 1'],
        ])));

        $result = $this->apifyClient->getDataset('dataset-123', [
            'limit' => 10,
            'offset' => 20,
            'fields' => ['title', 'url'],
        ]);

        expect($result)->toBeArray();
    });

    it('throws exception when dataset not found', function () {
        $this->mockHandler->append(new RequestException(
            'Not Found',
            new Request('GET', 'datasets/invalid/items'),
            new Response(404, [], json_encode(['error' => 'Dataset not found']))
        ));

        expect(fn () => $this->apifyClient->getDataset('invalid-dataset'))
            ->toThrow(ApifyException::class, 'Failed to get dataset');
    });
});

describe('getKeyValueStore', function () {
    it('can retrieve json data from key-value store', function () {
        $mockData = ['key' => 'value', 'number' => 42];

        $this->mockHandler->append(new Response(200, [
            'Content-Type' => 'application/json',
        ], json_encode($mockData)));

        $result = $this->apifyClient->getKeyValueStore('store-123', 'test-key');

        expect($result)
            ->toBeArray()
            ->toHaveKey('key', 'value')
            ->toHaveKey('number', 42);
    });

    it('can retrieve text data from key-value store', function () {
        $textData = 'This is some text data';

        $this->mockHandler->append(new Response(200, [
            'Content-Type' => 'text/plain',
        ], $textData));

        $result = $this->apifyClient->getKeyValueStore('store-123', 'text-key');

        expect($result)->toBe($textData);
    });

    it('throws exception when key not found', function () {
        $this->mockHandler->append(new RequestException(
            'Not Found',
            new Request('GET', 'key-value-stores/store-123/records/missing-key'),
            new Response(404)
        ));

        expect(fn () => $this->apifyClient->getKeyValueStore('store-123', 'missing-key'))
            ->toThrow(ApifyException::class, 'Failed to get key-value store');
    });
});

describe('setKeyValueStore', function () {
    it('can set json data in key-value store', function () {
        $this->mockHandler->append(new Response(201));

        $result = $this->apifyClient->setKeyValueStore('store-123', 'test-key', [
            'data' => 'value',
        ]);

        expect($result)->toBeTrue();
    });

    it('can set text data in key-value store', function () {
        $this->mockHandler->append(new Response(201));

        $result = $this->apifyClient->setKeyValueStore(
            'store-123',
            'text-key',
            'Plain text content',
            'text/plain'
        );

        expect($result)->toBeTrue();
    });

    it('throws exception on set failure', function () {
        $this->mockHandler->append(new RequestException(
            'Bad Request',
            new Request('PUT', 'key-value-stores/store-123/records/test-key'),
            new Response(400)
        ));

        expect(fn () => $this->apifyClient->setKeyValueStore('store-123', 'test-key', ['data' => 'value']))
            ->toThrow(ApifyException::class, 'Failed to set key-value store');
    });
});

describe('getActorRun', function () {
    it('can retrieve actor run details', function () {
        $runData = [
            'data' => [
                'id' => 'run-123',
                'status' => 'RUNNING',
                'startedAt' => '2024-01-01T00:00:00.000Z',
            ],
        ];

        $this->mockHandler->append(new Response(200, [], json_encode($runData)));

        $result = $this->apifyClient->getActorRun('run-123');

        expect($result)
            ->toBeArray()
            ->toHaveKey('data')
            ->and($result['data'])
            ->toHaveKey('id', 'run-123')
            ->toHaveKey('status', 'RUNNING');
    });

    it('throws exception when run not found', function () {
        $this->mockHandler->append(new RequestException(
            'Not Found',
            new Request('GET', 'actor-runs/invalid-run'),
            new Response(404)
        ));

        expect(fn () => $this->apifyClient->getActorRun('invalid-run'))
            ->toThrow(ApifyException::class, 'Failed to get actor run');
    });
});

describe('abortActorRun', function () {
    it('can abort a running actor', function () {
        $abortData = [
            'data' => [
                'id' => 'run-123',
                'status' => 'ABORTING',
            ],
        ];

        $this->mockHandler->append(new Response(200, [], json_encode($abortData)));

        $result = $this->apifyClient->abortActorRun('run-123');

        expect($result)
            ->toBeArray()
            ->toHaveKey('data')
            ->and($result['data'])
            ->toHaveKey('status', 'ABORTING');
    });

    it('throws exception when cannot abort run', function () {
        $this->mockHandler->append(new RequestException(
            'Bad Request',
            new Request('POST', 'actor-runs/run-123/abort'),
            new Response(400)
        ));

        expect(fn () => $this->apifyClient->abortActorRun('run-123'))
            ->toThrow(ApifyException::class, 'Failed to abort actor run');
    });
});

describe('getUser', function () {
    it('can retrieve user information', function () {
        $userData = [
            'data' => [
                'id' => 'user-123',
                'username' => 'testuser',
                'email' => 'test@example.com',
            ],
        ];

        $this->mockHandler->append(new Response(200, [], json_encode($userData)));

        $result = $this->apifyClient->getUser();

        expect($result)
            ->toBeArray()
            ->toHaveKey('data')
            ->and($result['data'])
            ->toHaveKey('username', 'testuser')
            ->toHaveKey('email', 'test@example.com');
    });

    it('throws exception on authentication error', function () {
        $this->mockHandler->append(new RequestException(
            'Unauthorized',
            new Request('GET', 'users/me'),
            new Response(401)
        ));

        expect(fn () => $this->apifyClient->getUser())
            ->toThrow(ApifyException::class, 'Failed to get user info');
    });
});

describe('listActors', function () {
    it('can list all actors', function () {
        $actorsData = [
            'data' => [
                'items' => [
                    ['id' => 'actor-1', 'name' => 'Web Scraper'],
                    ['id' => 'actor-2', 'name' => 'Data Extractor'],
                ],
            ],
        ];

        $this->mockHandler->append(new Response(200, [], json_encode($actorsData)));

        $result = $this->apifyClient->listActors();

        expect($result)
            ->toBeArray()
            ->toHaveKey('data')
            ->and($result['data']['items'])
            ->toHaveCount(2);
    });

    it('can list actors with options', function () {
        $this->mockHandler->append(new Response(200, [], json_encode([
            'data' => ['items' => []],
        ])));

        $result = $this->apifyClient->listActors([
            'my' => true,
            'limit' => 10,
            'offset' => 5,
        ]);

        expect($result)->toBeArray();
    });

    it('throws exception on list error', function () {
        $this->mockHandler->append(new RequestException(
            'Server Error',
            new Request('GET', 'acts'),
            new Response(500)
        ));

        expect(fn () => $this->apifyClient->listActors())
            ->toThrow(ApifyException::class, 'Failed to list actors');
    });
});
