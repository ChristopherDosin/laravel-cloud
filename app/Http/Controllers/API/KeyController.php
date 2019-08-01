<?php

namespace App\Http\Controllers\API;

use App\IpAddress;
use Illuminate\Http\Request;
use App\Scripts\AddKeyToServer;
use App\Jobs\RemoveKeyFromServer;
use App\Http\Controllers\Controller;

class KeyController extends Controller
{
    /**
     * Add the user's SSH key to the given server.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $addressable = $this->addressable($request);

        $this->authorize('view', $addressable->project);

        $task = $addressable->run(new AddKeyToServer(
            'cloud-user-'.$request->user()->id, $request->user()->public_key
        ));

        if (! $task->successful()) {
            return response('', 504);
        }

        RemoveKeyFromServer::dispatch(
            $request->user(), $addressable
        )->delay(30);

        return ['key' => $request->user()->private_key];
    }

    /**
     * Get the addressable instance for the request.
     *
     * @param  Request  $request
     * @return mixed
     */
    protected function addressable(Request $request)
    {
        return IpAddress::where(
            'public_address', $request->ip_address
        )->firstOrFail()->addressable;
    }
}
