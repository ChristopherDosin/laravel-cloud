<?php

namespace App\Exceptions;

use Exception;
use App\Contracts\Provisionable;

class ProvisioningTimeout extends Exception
{
    /**
     * Create a new exception for a provisionable server.
     *
     * @param  Provisionable  $provisionable
     * @return static
     */
    public static function for(Provisionable $provisionable)
    {
        $project = $provisionable->projectId() ?? 'Deleted';

        $type = get_class($provisionable);

        return new static("Timed out while provisioning [{$type}] server for project [{$project}]");
    }
}
