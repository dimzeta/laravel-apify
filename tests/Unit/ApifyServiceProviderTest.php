<?php

use Apify\Laravel\ApifyClient;
use Apify\Laravel\ApifyException;

describe('ApifyServiceProvider', function () {
    it('registers the apify service in the container', function () {
        expect(app()->bound('apify'))->toBeTrue();
        expect(app('apify'))->toBeInstanceOf(ApifyClient::class);
    });

    it('aliases ApifyClient in the container', function () {
        expect(app()->bound(ApifyClient::class))->toBeTrue();
        expect(app(ApifyClient::class))->toBeInstanceOf(ApifyClient::class);
    });

    it('creates singleton instance', function () {
        $instance1 = app('apify');
        $instance2 = app('apify');

        expect($instance1)->toBe($instance2);
    });

    it('throws exception when api token is not configured', function () {
        config(['apify.api_token' => null]);

        expect(fn () => app('apify'))
            ->toThrow(ApifyException::class, 'Apify API token is not configured');
    });

    it('uses configuration options when creating client', function () {
        config([
            'apify.api_token' => 'test-token',
            'apify.timeout' => 60,
            'apify.base_uri' => 'https://custom.api.com/',
        ]);

        app()->forgetInstance('apify');

        $client = app('apify');

        expect($client)->toBeInstanceOf(ApifyClient::class);
    });

    it('publishes config file when running in console', function () {
        $this->artisan('vendor:publish', ['--tag' => 'apify-config'])
            ->assertExitCode(0);

        expect(file_exists(config_path('apify.php')))->toBeTrue();
    });
});
