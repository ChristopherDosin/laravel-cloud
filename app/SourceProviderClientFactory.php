<?php

namespace App;

use App\Services\GitHub;
use InvalidArgumentException;

class SourceProviderClientFactory
{
    /**
     * Create a source control provider client instance for the given provider.
     *
     * @param  \App\SourceProvider  $source
     * @return \App\Contracts\SourceProviderClient
     */
    public function make(SourceProvider $source)
    {
        switch ($source->type) {
            case 'GitHub':
                return new GitHub($source);
            default:
                throw new InvalidArgumentException("Invalid source control provider type.");
        }
    }
}
