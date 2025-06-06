<?php

declare(strict_types=1);

namespace Apify\Laravel;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ApifyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('apify')
            ->hasConfigFile();
    }

    public function registeringPackage(): void
    {
        $this->app->singleton('apify', function ($app) {
            $apiToken = config('apify.api_token');

            if (! $apiToken) {
                throw new ApifyException('Apify API token is not configured. Please set APIFY_API_TOKEN in your .env file.');
            }

            $options = array_filter([
                'timeout' => config('apify.timeout'),
                'base_uri' => config('apify.base_uri'),
            ]);

            return new ApifyClient($apiToken, $options);
        });

        $this->app->alias('apify', ApifyClient::class);
    }
}
