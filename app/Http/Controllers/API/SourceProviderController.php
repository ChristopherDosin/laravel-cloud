<?php

namespace App\Http\Controllers\API;

use App\SourceProvider;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class SourceProviderController extends Controller
{
    /**
     * Get all of the source control providers for the current user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request)
    {
        return $request->user()->sourceProviders;
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
            'name' => 'required|max:255|unique:source_providers,name,NULL,id,user_id,'.$request->user()->id,
            'type' => 'required|in:GitHub',
            'meta' => 'required|array',
        ]);

        $source = $request->user()->sourceProviders()->create([
            'name' => $request->name,
            'type' => $request->type,
            'meta' => $request->meta,
        ]);

        if (! $source->client()->valid()) {
            $source->delete();

            throw ValidationException::withMessages([
                'meta' => ['The given credentials are invalid.'],
            ]);
        }

        return response()->json($source, 201);
    }

    /**
     * Delete the given source control provider.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\SourceProvider  $provider
     * @return Response
     */
    public function destroy(Request $request, SourceProvider $provider)
    {
        abort_if(! $request->user()->sourceProviders->contains($provider), 403);

        if (count($provider->projects) > 0) {
            throw ValidationException::withMessages(['balancer' => [
                'This source control provider is being used by active projects.'
            ]]);
        }

        $provider->delete();
    }
}
