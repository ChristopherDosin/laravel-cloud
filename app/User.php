<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'last_alert_received_at' => 'datetime',
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
        'api_token',
        'meta',
        'password',
        'private_key',
        'public_worker_key',
        'private_worker_key',
        'provider_key_id',
        'remember_token',
    ];

    /**
     * Get all of the server providers owned by the user.
     */
    public function serverProviders()
    {
        return $this->hasMany(ServerProvider::class);
    }

    /**
     * Get all of the source control providers owned by the user.
     */
    public function sourceProviders()
    {
        return $this->hasMany(SourceProvider::class);
    }

    /**
     * Get all of the storage providers owned by the user.
     */
    public function storageProviders()
    {
        return $this->hasMany(StorageProvider::class);
    }

    /**
     * All of the projects that belong to the user.
     */
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /**
     * All of the projects that have been shared with the user.
     */
    public function teamProjects()
    {
        return $this->belongsToMany(
            Project::class, 'project_users', 'user_id', 'project_id'
        )->using(Collaborator::class);
    }

    /**
     * Determine if the user has access to the given project.
     *
     * @param  Project  $project
     * @return bool
     */
    public function canAccessProject($project)
    {
        return $this->projects->contains($project) ||
               $this->teamProjects->contains($project);
    }

    /**
     * Set the SSH key attributes on the model.
     *
     * @param  object  $value
     * @return void
     */
    public function setKeypairAttribute($value)
    {
        $this->attributes = [
            'public_key' => $value->publicKey,
            'private_key' => $value->privateKey,
        ] + $this->attributes;
    }

    /**
     * Set the SSH key attributes on the model.
     *
     * @param  object  $value
     * @return void
     */
    public function setWorkerKeypairAttribute($value)
    {
        $this->attributes = [
            'public_worker_key' => $value->publicKey,
            'private_worker_key' => $value->privateKey,
        ] + $this->attributes;
    }

    /**
     * Revoke API tokens with the given name.
     *
     * @param  string  $name
     * @return void
     */
    public function revokeTokens($name)
    {
        $this->tokens()->where('name', $name)->update(['revoked' => true]);
    }

    /**
     * Get the path to the user's worker SSH key.
     *
     * @return string
     */
    public function keyPath()
    {
        return SecureShellKey::storeFor($this);
    }
}
