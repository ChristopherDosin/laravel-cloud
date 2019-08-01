<?php

namespace App\Http\Controllers\API;

use App\Project;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProjectRequest;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    /**
     * Get all of the projects for the current user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request)
    {
        return $request->user()->projects->merge(
            $request->user()->teamProjects
        )->sortBy('name')->reject->archived;
    }

    /**
     * Get the project with the given ID.
     *
     * @param  \App\Project  $project
     * @return Response
     */
    public function show(Project $project)
    {
        $this->authorize('view', $project);

        return $project;
    }

    /**
     * Handle the incoming request.
     *
     * @param  CreateProjectRequest  $request
     * @return mixed
     */
    public function store(CreateProjectRequest $request)
    {
        $project = $request->user()->projects()->create([
            'name' => $request->name,
            'server_provider_id' => $request->server_provider_id,
            'region' => $request->region,
            'source_provider_id' => $request->source_provider_id,
            'repository' => $request->repository,
        ]);

        if ($request->database) {
            $project->provisionDatabase(
                $request->database, $request->database_size
            );
        }

        return response()->json($project, 201);
    }

    /**
     * Destroy the given project.
     *
     * @param  Request  $request
     * @param  \App\Project  $project
     * @return Response
     */
    public function destroy(Request $request, Project $project)
    {
        $this->authorize('delete', $project);

        if (! $project->allStacks()->every->isProvisioned() ||
            $project->allStacks()->contains->isDeploying()) {
            throw ValidationException::withMessages([
                'stack' => ['This project has stacks that are provisioning or deploying.'],
            ]);
        }

        $project->purge();

        $project->archive();
    }
}
