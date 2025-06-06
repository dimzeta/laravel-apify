# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

### Testing
```bash
composer test                    # Run all tests with Pest
composer test-coverage          # Run tests with coverage
vendor/bin/pest --filter "test name"  # Run specific test
```

### Code Quality
```bash
composer analyse                # Run PHPStan static analysis 
composer format                 # Format code with Laravel Pint
```

### Package Development
```bash
composer prepare                # Discover packages for testing
php artisan vendor:publish --tag=apify-config  # Publish config in Laravel app
```

## Architecture

This is a **Laravel package** that provides integration with the Apify web scraping platform. The architecture follows Laravel package development best practices with strict separation of concerns:

### Core Components

**ApifyClient** (`src/ApifyClient.php`)
- Main HTTP client using Guzzle for Apify API communication
- Framework-agnostic design - does NOT use Laravel facades
- Handles authentication, request formatting, and error handling
- Methods: `runActor()`, `getDataset()`, `getKeyValueStore()`, `setKeyValueStore()`, `getActorRun()`, `abortActorRun()`, `getUser()`, `listActors()`

**ApifyServiceProvider** (`src/ApifyServiceProvider.php`) 
- Registers ApifyClient as singleton in Laravel container
- Binds configuration from `config/apify.php`
- Publishes configuration file
- Creates alias for dependency injection

**Apify Facade** (`src/Facades/Apify.php`)
- Laravel facade providing static access to ApifyClient methods
- Proxies all ApifyClient methods for convenient usage

**ApifyException** (`src/ApifyException.php`)
- Custom exception for Apify API errors
- Extends base Exception class

### Configuration Structure

The package uses `config/apify.php` with:
- `api_token`: Apify API token (from APIFY_API_TOKEN env)
- `timeout`: Request timeout (default: 30s)
- `base_uri`: API endpoint (default: https://api.apify.com/v2/)
- `default_actor_options`: Default memory (512MB) and waitForFinish (60s)
- `webhook_url` and `webhook_events`: For actor run notifications

### Testing Framework

Uses **Pest PHP** with comprehensive test coverage:
- **Unit tests**: Individual class behavior
- **Feature tests**: Laravel integration scenarios  
- **Architecture tests**: Enforces structural constraints (strict types, proper inheritance, no Laravel facades in core client)

### Development Constraints

**Architectural Rules** (enforced by tests):
- ApifyClient must remain framework-agnostic (no Laravel facades)
- All classes must declare strict types
- Facades must extend Laravel's Facade class
- ServiceProvider must extend Spatie's PackageServiceProvider
- No debug functions (dd, dump, var_dump, print_r)
- ApifyClient is intentionally not final (can be extended)

**Multi-Version Support**:
- PHP 8.2+
- Laravel 10.x, 11.x, 12.x
- Tested on Ubuntu and Windows in CI

### Key Dependencies

- **spatie/laravel-package-tools**: Package development utilities
- **guzzlehttp/guzzle**: HTTP client for API communication  
- **pestphp/pest**: Testing framework with architecture plugin
- **larastan/larastan**: Laravel-specific PHPStan rules

Always run `composer test` before committing to ensure all architectural constraints are maintained.