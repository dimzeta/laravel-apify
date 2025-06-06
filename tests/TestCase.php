<?php

namespace Apify\Laravel\Tests;

use Apify\Laravel\ApifyServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Apify\\Laravel\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            ApifyServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        config()->set('apify.api_token', 'test-token');
        config()->set('apify.timeout', 30);
        config()->set('apify.base_uri', 'https://api.apify.com/v2/');
    }
}
