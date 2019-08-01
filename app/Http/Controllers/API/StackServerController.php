<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StackServerController extends Controller
{
    /**
     * Get all of the servers for the stack.
     *
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request)
    {
        $this->authorize('view', $request->stack->project());

        $stack = $request->stack;

        $stack->load('appServers.address', 'webServers.address', 'workerServers.address');

        return [
            'app' => $stack->appServers->all(),
            'web' => $stack->webServers->all(),
            'worker' => $stack->workerServers->all(),
        ];
    }
}
