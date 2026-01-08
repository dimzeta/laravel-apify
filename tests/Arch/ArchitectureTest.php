<?php

use Apify\Laravel\ApifyClient;

describe('Architecture', function () {
    test('ApifyClient does not use Laravel facades')
        ->expect('Apify\Laravel\ApifyClient')
        ->not->toUse([
            'Illuminate\Support\Facades\App',
            'Illuminate\Support\Facades\Config',
            'Illuminate\Support\Facades\Cache',
            'Illuminate\Support\Facades\Log',
        ]);

    test('facades extend Facade')
        ->expect('Apify\Laravel\Facades')
        ->toExtend('Illuminate\Support\Facades\Facade');

    test('service providers extend ServiceProvider or PackageServiceProvider')
        ->expect('Apify\Laravel\ApifyServiceProvider')
        ->toExtend('Spatie\LaravelPackageTools\PackageServiceProvider');

    test('exceptions extend Exception')
        ->expect('Apify\Laravel\ApifyException')
        ->toExtend('Exception');

    test('no debug functions are used')
        ->expect(['dd', 'dump', 'var_dump', 'print_r'])
        ->not->toBeUsed();

    test('strict types are declared')
        ->expect('Apify\Laravel')
        ->toUseStrictTypes();

    test('ApifyClient is final or can be extended safely')
        ->expect(ApifyClient::class)
        ->not->toBeFinal();

    test('no echo or print statements')
        ->expect('Apify\Laravel')
        ->not->toUse(['echo', 'print']);

    test('uses proper return types')
        ->expect('Apify\Laravel\ApifyClient')
        ->toHaveMethod('runActor')
        ->and('Apify\Laravel\ApifyClient')
        ->toHaveMethod('getDataset');
});
