<?php

namespace App\Http\Controllers\API;

use App\Project;
use App\Database;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateDatabaseRequest;

class DatabaseController extends Controller
{
    /**
     * Get all of the databases for the given project.
     *
     * @param  Project  $project
     * @return Response
     */
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        return $project->databases()->with('address')->get();
    }

    /**
     * Handle the incoming request.
     *
     * @param  CreateDatabaseRequest  $request
     * @return mixed
     */
    public function store(CreateDatabaseRequest $request)
    {
        $this->authorize('view', $request->project);

        $database = $request->project->provisionDatabase(
            $request->name, $request->size
        );

        return response()->json($database, 201);
    }

    /**
     * Delete the given database.
     *
     * @param  Database  $database
     * @return Response
     */
    public function destroy(Database $database)
    {
        $this->authorize('delete', $database);

        $database->delete();
    }
}
