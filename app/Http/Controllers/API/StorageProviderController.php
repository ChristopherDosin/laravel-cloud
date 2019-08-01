<?php

namespace App\Http\Controllers\API;

use App\StorageProvider;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class StorageProviderController extends Controller
{
    /**
     * Get all of the storage providers for the user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request)
    {
        return $request->user()->storageProviders;
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
            'name' => 'required|max:255|unique:storage_providers,name,NULL,id,user_id,'.$request->user()->id,
            'type' => 'required|in:S3',
            'meta' => 'required|array',
        ]);

        $provider = $request->user()->storageProviders()->create([
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

    /**
     * Delete the given storage provider.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\StorageProvider  $provider
     * @return Response
     */
    public function destroy(Request $request, StorageProvider $provider)
    {
        abort_if(! $request->user()->storageProviders->contains($provider), 403);

        $provider->delete();
    }
}
