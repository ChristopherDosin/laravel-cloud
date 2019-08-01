<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DaemonGeneration extends Model
{
    /**
     * Get the stack the daemon generation belongs to.
     */
    public function stack()
    {
        return $this->belongsTo(Stack::class, 'stack_id');
    }
}
