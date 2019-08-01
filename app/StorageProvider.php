<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Facades\App\StorageProviderClientFactory;

class StorageProvider extends Model
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => 'json',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'meta',
    ];

    /**
     * Get the user that owns the storage provider.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all of the database backups attached to the provider.
     */
    public function backups()
    {
        return $this->hasMany(DatabaseBackup::class);
    }

    /**
     * Get the name of the storage bucket.
     *
     * @return string
     */
    public function bucket()
    {
        return $this->meta['bucket'];
    }

    /**
     * Get a storage provider client for the provider.
     *
     * @return \App\Contracts\StorageProviderClient
     */
    public function client()
    {
        return StorageProviderClientFactory::make($this);
    }
}
