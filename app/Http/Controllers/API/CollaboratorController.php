<?php

namespace App\Http\Controllers\API;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CollaboratorController extends Controller
{
    /**
     * Get all of the collaborators associated with the user's projects.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $projects = $request->user()->projects()->with('collaborators')->get();

        $collaborators = collect();

        foreach ($projects as $project) {
            $collaborators = $collaborators->merge($project->collaborators);
        }

        return $collaborators->unique(function ($collaborator) {
            return $collaborator->id;
        });
    }

    /**
     * Remove a user from all of the user's owned projects.
     *
     * @param  Request  $request
     * @return Response
     */
    public function destroy(Request $request)
    {
        $user = User::where('email', $request->email)->firstOrFail();

        foreach ($request->user()->projects as $project) {
            $project->stopSharingWith($user);
        }
    }
}
