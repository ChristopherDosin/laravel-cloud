<?php

namespace App\Policies;

use App\User;
use App\Project;
use App\Environment;
use Illuminate\Auth\Access\HandlesAuthorization;

class EnvironmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can delete the environment.
     *
     * @param  \App\User  $user
     * @param  \App\Environment  $environment
     * @return mixed
     */
    public function delete(User $user, Environment $environment)
    {
        return $user->projects->contains($environment->project) ||
               $environment->creator->id == $user->id;
    }
}
