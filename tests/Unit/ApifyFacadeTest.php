<?php

use Apify\Laravel\ApifyClient;
use Apify\Laravel\Facades\Apify;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

describe('Apify Facade', function () {
    beforeEach(function () {
        $this->mockHandler = new MockHandler;
        $handlerStack = HandlerStack::create($this->mockHandler);

        app()->singleton('apify', function () use ($handlerStack) {
            return new ApifyClient('test-token', [
                'handler' => $handlerStack,
            ]);
        });
    });

    it('resolves to ApifyClient instance', function () {
        expect(Apify::getFacadeRoot())->toBeInstanceOf(ApifyClient::class);
    });

    it('can call runActor through facade', function () {
        $this->mockHandler->append(new Response(200, [], json_encode([
            'data' => ['id' => 'run-123', 'status' => 'SUCCEEDED'],
        ])));

        $result = Apify::runActor('test-actor', ['url' => 'https://example.com']);

        expect($result)
            ->toBeArray()
            ->toHaveKey('data')
            ->and($result['data'])
            ->toHaveKey('id', 'run-123');
    });

    it('can call getDataset through facade', function () {
        $mockData = [
            ['title' => 'Item 1', 'url' => 'https://example.com/1'],
            ['title' => 'Item 2', 'url' => 'https://example.com/2'],
        ];

        $this->mockHandler->append(new Response(200, [], json_encode($mockData)));

        $result = Apify::getDataset('dataset-123');

        expect($result)
            ->toBeArray()
            ->toHaveCount(2)
            ->and($result[0])
            ->toHaveKey('title', 'Item 1');
    });

    it('can call getKeyValueStore through facade', function () {
        $this->mockHandler->append(new Response(200, [
            'Content-Type' => 'application/json',
        ], json_encode(['key' => 'value'])));

        $result = Apify::getKeyValueStore('store-123', 'test-key');

        expect($result)
            ->toBeArray()
            ->toHaveKey('key', 'value');
    });

    it('can call setKeyValueStore through facade', function () {
        $this->mockHandler->append(new Response(201));

        $result = Apify::setKeyValueStore('store-123', 'test-key', ['data' => 'value']);

        expect($result)->toBeTrue();
    });

    it('can call getActorRun through facade', function () {
        $this->mockHandler->append(new Response(200, [], json_encode([
            'data' => ['id' => 'run-123', 'status' => 'RUNNING'],
        ])));

        $result = Apify::getActorRun('run-123');

        expect($result)
            ->toBeArray()
            ->toHaveKey('data');
    });

    it('can call abortActorRun through facade', function () {
        $this->mockHandler->append(new Response(200, [], json_encode([
            'data' => ['id' => 'run-123', 'status' => 'ABORTING'],
        ])));

        $result = Apify::abortActorRun('run-123');

        expect($result)
            ->toBeArray()
            ->toHaveKey('data');
    });

    it('can call getUser through facade', function () {
        $this->mockHandler->append(new Response(200, [], json_encode([
            'data' => ['id' => 'user-123', 'username' => 'testuser'],
        ])));

        $result = Apify::getUser();

        expect($result)
            ->toBeArray()
            ->toHaveKey('data');
    });

    it('can call listActors through facade', function () {
        $this->mockHandler->append(new Response(200, [], json_encode([
            'data' => ['items' => [['id' => 'actor-1', 'name' => 'Test Actor']]],
        ])));

        $result = Apify::listActors();

        expect($result)
            ->toBeArray()
            ->toHaveKey('data');
    });
});
