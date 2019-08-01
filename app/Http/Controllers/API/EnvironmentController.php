<?php

namespace App\Http\Controllers\API;

use App\Environment;
use Illuminate\Http\Request;
use Illuminate\Encryption\Encrypter;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class EnvironmentController extends Controller
{
    /**
     * Get all of the environments for the project.
     *
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request)
    {
        $this->authorize('view', $request->project);

        return $request->project->environments;
    }

    /**
     * Retrieve the given environment.
     *
     * @param  Request  $request
     * @param  \App\Environment  $environment
     * @return Response
     */
    public function show(Request $request, Environment $environment)
    {
        $this->authorize('view', $environment->project);

        return $environment->makeVisible(['variables']);
    }

    /**
     * Create a new environment.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $this->authorize('view', $request->project);

        $request->validate([
            'name' => 'required|string|max:255|unique:environments,name,NULL,id,project_id,'.$request->project->id,
            'variables' => 'string|max:50000'
        ]);

        return response()->json($request->project->environments()->create([
            'creator_id' => $request->user()->id,
            'name' => $request->name,
            'encryption_key' => 'base64:'.base64_encode(Encrypter::generateKey(config('app.cipher'))),
            'variables' => $request->variables ?? '',
        ]), 201);
    }

    /**
     * Create a new environment.
     *
     * @param  Request  $request
     * @return Response
     */
    public function update(Request $request)
    {
        $this->authorize('view', $request->environment->project);

        $request->validate([
            'variables' => 'nullable|string|max:50000'
        ]);

        return tap($request->environment)->update([
            'variables' => $request->variables ?? '',
        ]);
    }

    /**
     * Delete an environment.
     *
     * @param  Request  $request
     * @param  \App\Environment  $environment
     * @return Response
     */
    public function destroy(Request $request, Environment $environment)
    {
        $this->authorize('delete', $environment);

        if (! $environment->stacks->every->isProvisioned() ||
            $environment->stacks->contains->isDeploying()) {
            throw ValidationException::withMessages([
                'stack' => ['This environment has stacks that are provisioning or deploying.'],
            ]);
        }

        $environment->delete();
    }
}
