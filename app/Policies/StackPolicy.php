<?php

namespace App\Policies;

use App\User;
use App\Stack;
use App\Project;
use App\Environment;
use Illuminate\Auth\Access\HandlesAuthorization;

class StackPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the stack.
     *
     * @param  \App\User  $user
     * @param  \App\Stack  $stack
     * @return mixed
     */
    public function view(User $user, Stack $stack)
    {
        return $user->canAccessProject($stack->environment->project);
    }

    /**
     * Determine whether the user can delete the stack.
     *
     * @param  \App\User  $user
     * @param  \App\Stack  $stack
     * @return mixed
     */
    public function delete(User $user, Stack $stack)
    {
        return $stack->creator->id == $user->id ||
               $user->projects->contains($stack->environment->project);
    }
}
