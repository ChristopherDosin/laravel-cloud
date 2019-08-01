<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class ServerProviderController extends Controller
{
    /**
     * Get all of the server providers for the current user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request)
    {
        return $request->user()->serverProviders;
    }

    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:255|unique:server_providers,name,NULL,id,user_id,'.$request->user()->id,
            'type' => 'required|in:DigitalOcean',
            'meta' => 'required|array',
        ]);

        $provider = $request->user()->serverProviders()->create([
            'name' => $request->name,
            'type' => $request->type,
            'meta' => $request->meta,
        ]);

        if (! $provider->client()->valid()) {
            $provider->delete();

            throw ValidationException::withMessages([
                'meta' => ['The given credentials are invalid.'],
            ]);
        }

        return response()->json($provider, 201);
    }
}
