<?php

namespace App\Policies;

use App\User;
use App\Project;
use App\Balancer;
use Illuminate\Auth\Access\HandlesAuthorization;

class BalancerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can delete the balancer.
     *
     * @param  \App\User  $user
     * @param  \App\Balancer  $balancer
     * @return mixed
     */
    public function delete(User $user, Balancer $balancer)
    {
        return $user->projects->contains($balancer->project);
    }
}
