<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Facades\App\ServerProviderClientFactory;

class ServerProvider extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => 'json',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'meta',
    ];

    /**
     * The user that owns the provider.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Determine if the given region is valid for this provider.
     *
     * @param  string  $region
     * @return bool
     */
    public function validRegion($region)
    {
        return array_key_exists($region, $this->regions());
    }

    /**
     * Determine if the given size is valid for this provider.
     *
     * @param  string  $size
     * @return bool
     */
    public function validSize($size)
    {
        return array_key_exists($size, $this->sizes());
    }

    /**
     * Get all of the valid regions for the provider.
     *
     * @return array
     */
    public function regions()
    {
        return $this->client()->regions();
    }

    /**
     * Get all of the valid server sizes for the provider.
     *
     * @return array
     */
    public function sizes()
    {
        return $this->client()->sizes();
    }

    /**
     * Get a provider client for the provider.
     *
     * @return \App\Contracts\ServerProviderClient
     */
    public function client()
    {
        return ServerProviderClientFactory::make($this);
    }
}
