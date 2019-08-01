<?php

namespace App;

use App\Services\S3;
use InvalidArgumentException;

class StorageProviderClientFactory
{
    /**
     * Create a storage provider client instance for the given provider.
     *
     * @param  \App\StorageProvider  $provider
     * @return \App\Contracts\StorageProviderClient
     */
    public function make(StorageProvider $provider)
    {
        switch ($provider->type) {
            case 'S3':
                return new S3($provider);
            default:
                throw new InvalidArgumentException("Invalid provider type.");
        }
    }
}
