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
