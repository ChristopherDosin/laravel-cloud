<?php

namespace App\Http\Controllers\API;

use App\Stack;
use App\Http\Controllers\Controller;

class ServerConfigurationController extends Controller
{
    /**
     * Update the stack's server configuration.
     *
     * @param  \App\Stack  $stack
     * @return mixed
     */
    public function update(Stack $stack)
    {
        $this->authorize('view', $stack);

        $stack->syncServers();
    }
}
