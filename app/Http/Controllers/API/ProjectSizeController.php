<?php

namespace App\Http\Controllers\API;

use App\Project;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProjectSizeController extends Controller
{
    /**
     * Get all of the regions for the given provider.
     *
     * @param  Request  $request
     * @param  Project  $project
     * @return Response
     */
    public function index(Request $request, Project $project)
    {
        return $project->serverProvider->sizes();
    }
}
