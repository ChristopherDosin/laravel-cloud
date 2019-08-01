<?php

namespace App\Policies;

use App\User;
use App\Database;
use Illuminate\Auth\Access\HandlesAuthorization;

class DatabaseRestorePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can restore databases.
     *
     * @param  \App\User  $user
     * @param  \App\Database  $database
     * @return mixed
     */
    public function create(User $user, Database $database)
    {
        return $user->projects->contains($database->project);
    }
}
