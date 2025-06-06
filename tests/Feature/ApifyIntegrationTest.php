<?php

use Apify\Laravel\ApifyClient;
use Apify\Laravel\Facades\Apify;

describe('Apify Integration', function () {
    it('can inject ApifyClient into service classes', function () {
        $service = new class(app(ApifyClient::class))
        {
            public function __construct(private ApifyClient $apify) {}

            public function getClient(): ApifyClient
            {
                return $this->apify;
            }
        };

        expect($service->getClient())->toBeInstanceOf(ApifyClient::class);
    });

    it('facade and service container return same instance', function () {
        $facadeInstance = Apify::getFacadeRoot();
        $containerInstance = app('apify');

        expect($facadeInstance)->toBe($containerInstance);
    });

    it('respects configuration changes', function () {
        config(['apify.api_token' => 'new-token']);

        app()->forgetInstance('apify');

        $client = app('apify');

        expect($client)->toBeInstanceOf(ApifyClient::class);
    });

    it('can be used in Laravel job classes', function () {
        $job = new class
        {
            public function handle(ApifyClient $apify): string
            {
                return get_class($apify);
            }
        };

        $result = $job->handle(app(ApifyClient::class));

        expect($result)->toBe(ApifyClient::class);
    });

    it('works with Laravel service binding', function () {
        app()->bind('scraper', function ($app) {
            return new class($app['apify'])
            {
                public function __construct(private ApifyClient $apify) {}

                public function scrape(): string
                {
                    return 'scraping with '.get_class($this->apify);
                }
            };
        });

        $scraper = app('scraper');

        expect($scraper->scrape())->toBe('scraping with '.ApifyClient::class);
    });
});
