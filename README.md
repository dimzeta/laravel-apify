# Laravel Apify Package

A Laravel package for integrating with the [Apify](https://apify.com) web scraping and automation platform.

## Installation

Install the package via Composer:

```bash
composer require flatroy/laravel-apify
```

The package will automatically register its service provider and facade.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=apify-config
```

Add your Apify API token to your `.env` file:

```env
APIFY_API_TOKEN=your_apify_api_token_here
```

You can find your API token in your [Apify account settings](https://console.apify.com/settings/integrations).

## Usage

### Using the Facade

```php
use Apify\Laravel\Facades\Apify;

// Run an actor
$result = Apify::runActor('actor-id', [
    'url' => 'https://example.com'
], [
    'waitForFinish' => 60
]);

// Get dataset results
$data = Apify::getDataset('dataset-id');

// Get user information
$user = Apify::getUser();
```

### Using Dependency Injection

```php
use Apify\Laravel\ApifyClient;

class ScrapingService
{
    public function __construct(private ApifyClient $apify)
    {
    }

    public function scrapeWebsite(string $url): array
    {
        return $this->apify->runActor('web-scraper', [
            'url' => $url
        ]);
    }
}
```

## Available Methods

### Running Actors

```php
// Run an actor and wait for completion (returns run metadata)
$result = Apify::runActor('actor-id', $input, [
    'waitForFinish' => 60, // seconds
    'memory' => 512,       // MB
]);

// Run without waiting
$result = Apify::runActor('actor-id', $input, [
    'waitForFinish' => 0
]);
```

### Running Actors Synchronously

For actors that complete in under 5 minutes, use synchronous methods to get results directly:

```php
// Run actor and get OUTPUT from key-value store
$output = Apify::runActorSync('actor-id', [
    'url' => 'https://example.com'
]);

// Run actor and get dataset items directly
$items = Apify::runActorSyncDataset('actor-id', [
    'url' => 'https://example.com'
], [
    'fields' => ['title', 'price'],
    'limit' => 100,
]);
```

**Note:** Synchronous endpoints have a 300-second timeout. If your actor takes longer, use `runActor()` with polling instead.

### Working with Datasets

```php
// Get all items from a dataset
$items = Apify::getDataset('dataset-id');

// Get items with pagination
$items = Apify::getDataset('dataset-id', [
    'limit' => 100,
    'offset' => 200
]);

// Get specific fields only
$items = Apify::getDataset('dataset-id', [
    'fields' => ['title', 'url', 'price']
]);
```

### Key-Value Stores

```php
// Get a record from key-value store
$record = Apify::getKeyValueStore('store-id', 'record-key');

// Set a record in key-value store
Apify::setKeyValueStore('store-id', 'record-key', [
    'data' => 'value'
]);

// Store binary data
Apify::setKeyValueStore('store-id', 'image.jpg', $binaryData, 'image/jpeg');
```

### Actor Runs Management

```php
// Get actor run details
$run = Apify::getActorRun('run-id');

// Abort a running actor
$result = Apify::abortActorRun('run-id');
```

### Listing Actors

```php
// List all available actors
$actors = Apify::listActors();

// List only your actors
$myActors = Apify::listActors(['my' => true]);

// Paginated listing
$actors = Apify::listActors([
    'limit' => 50,
    'offset' => 100
]);
```

## Error Handling

The package throws `ApifyException` for API errors:

```php
use Apify\Laravel\ApifyException;

try {
    $result = Apify::runActor('invalid-actor-id');
} catch (ApifyException $e) {
    Log::error('Apify error: ' . $e->getMessage());
}
```

## Configuration Options

The configuration file (`config/apify.php`) includes:

- `api_token`: Your Apify API token
- `base_uri`: API base URL (default: https://api.apify.com/v2/)
- `timeout`: Request timeout in seconds (default: 30)
- `default_actor_options`: Default options for running actors
- `webhook_url`: URL for webhook notifications
- `webhook_events`: Events to receive webhooks for

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email security@apify.com instead of using the issue tracker.
