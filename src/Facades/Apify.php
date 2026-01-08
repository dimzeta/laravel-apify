<?php

declare(strict_types=1);

namespace Apify\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array runActor(string $actorId, array $input = [], array $options = [])
 * @method static mixed runActorSync(string $actorId, array $input = [], array $options = [])
 * @method static array runActorSyncDataset(string $actorId, array $input = [], array $options = [])
 * @method static array getDataset(string $datasetId, array $options = [])
 * @method static mixed getKeyValueStore(string $storeId, string $key)
 * @method static bool setKeyValueStore(string $storeId, string $key, mixed $value, string $contentType = 'application/json')
 * @method static array getActorRun(string $runId)
 * @method static array abortActorRun(string $runId)
 * @method static array getUser()
 * @method static array listActors(array $options = [])
 *
 * @see \Apify\Laravel\ApifyClient
 */
class Apify extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'apify';
    }
}
