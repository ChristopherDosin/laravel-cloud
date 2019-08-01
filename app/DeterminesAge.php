<?php

namespace App;

use Carbon\Carbon;

trait DeterminesAge
{
    /**
     * Determine if the model is older than N minutes.
     *
     * @param  int  $minutes
     * @param  string  $attribute
     * @return bool
     */
    public function olderThan($minutes, $attribute = 'created_at')
    {
        return $this->{$attribute}->lte(Carbon::now()->subMinutes(10));
    }
}
