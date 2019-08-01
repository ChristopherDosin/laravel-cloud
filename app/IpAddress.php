<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IpAddress extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ip_addresses';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the addressable entity that owns the address.
     */
    public function addressable()
    {
        return $this->morphTo();
    }
}
