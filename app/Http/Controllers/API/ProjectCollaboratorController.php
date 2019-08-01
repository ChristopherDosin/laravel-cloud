<?php

namespace App\Http\Controllers\API;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class ProjectCollaboratorController extends Controller
{
    /**
     * Get all of the collaborators for the project.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('view', $request->project);

        return $request->project->collaborators;
    }

    /**
     * Add a collaborator to the project.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('updateCollaborators', $request->project);

        $this->validate($request, [
            'email' => 'required|max:255',
        ]);

        if (is_null($user = User::where('email', $request->email)->first())) {
            throw ValidationException::withMessages([
                'user' => ['Unable to find a user with that email address.'],
            ]);
        }

        $request->project->shareWith($user);
    }

    /**
     * Remove a collaborator to the project.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->authorize('updateCollaborators', $request->project);

        $this->validate($request, [
            'email' => 'required|max:255|in:'.implode(',', $this->emails($request)),
        ]);

        $request->project->stopSharingWith(
            User::where('email', $request->email)->firstOrFail()
        );
    }

    /**
     * Get the emails of all of the current collaborators.
     *
     * @param  Request  $request
     * @return array
     */
    protected function emails(Request $request)
    {
        return $request->project->collaborators->pluck('email')->all();
    }
}
