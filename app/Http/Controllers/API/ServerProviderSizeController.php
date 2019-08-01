<?php

namespace App\Http\Controllers\API;

use App\ServerProvider;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ServerProviderSizeController extends Controller
{
    /**
     * Get all of the regions for the given provider.
     *
     * @param  Request  $request
     * @param  \App\ServerProvider  $provider
     * @return Response
     */
    public function index(Request $request, ServerProvider $provider)
    {
        return $provider->sizes();
    }
}
