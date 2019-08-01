<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Collaborator extends Pivot
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'permissions' => 'json',
    ];
}
