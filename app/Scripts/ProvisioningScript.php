<?php

namespace App\Scripts;

use App\Contracts\Provisionable;

class ProvisioningScript extends Script
{
    /**
     * The provisionable instance.
     *
     * @var \App\Contracts\Provisionable
     */
    public $provisionable;

    /**
     * Create a new script instance.
     *
     * @param  \App\Contracts\Provisionable  $provisionable
     * @return void
     */
    public function __construct(Provisionable $provisionable)
    {
        $this->provisionable = $provisionable;
    }
}
