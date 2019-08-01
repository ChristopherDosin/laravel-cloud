<?php

namespace App;

use App\Events\ProjectShared;
use App\Events\ProjectUnshared;
use App\Jobs\ProvisionDatabase;
use App\Jobs\ProvisionBalancer;
use Illuminate\Database\Eloquent\Model;
use Facades\App\ServerProviderClientFactory;

class Project extends Model
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'archived' => 'boolean',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get all of the alerts for the project.
     */
    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * The user that owns the project.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * All of the users the project is shared with.
     */
    public function collaborators()
    {
        return $this->belongsToMany(
            User::class, 'project_users', 'project_id', 'user_id'
        )->using(Collaborator::class)->withPivot('permissions');
    }

    /**
     * Share the project with the given user.
     *
     * @param  User  $user
     * @return void
     */
    public function shareWith(User $user)
    {
        $this->collaborators()->detach($user);

        $this->collaborators()->attach($user, ['permissions' => []]);

        unset($this->collaborators);

        ProjectShared::dispatch($this, $user);
    }

    /**
     * Stop sharing the project with the given user.
     *
     * @param  User  $user
     * @return void
     */
    public function stopSharingWith(User $user)
    {
        $this->collaborators()->detach($user);

        ProjectUnshared::dispatch($this, $user);
    }

    /**
     * Get the server provider for the project.
     */
    public function serverProvider()
    {
        return $this->belongsTo(ServerProvider::class, 'server_provider_id');
    }

    /**
     * Get the source control provider for the project.
     */
    public function sourceProvider()
    {
        return $this->belongsTo(SourceProvider::class, 'source_provider_id');
    }

    /**
     * Get all of the tasks for the project.
     */
    public function tasks()
    {
        return $this->hasMany(Task::class)->latest();
    }

    /**
     * Get the database associated with the project.
     */
    public function databases()
    {
        return $this->hasMany(Database::class);
    }

    /**
     * Get all of the balancers associated with the project.
     */
    public function balancers()
    {
        return $this->hasMany(Balancer::class);
    }

    /**
     * Get the largest available balancer.
     *
     * @return \App\Balancer|null
     */
    public function largestAvailableBalancer()
    {
        return $this->balancersBySize()->first();
    }

    /**
     * Get all of the load balancers for the project sorted by size.
     *
     * @return \Illuminate\Database\Eloqeunt\Collection
     */
    public function balancersBySize()
    {
        return $this->balancers->sort(function ($a, $b) {
            return $a->sizeInMegabytes() <=> $b->sizeInMegabytes();
        })->reverse()->values();
    }

    /**
     * Get all of the certificates attached to the project.
     */
    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Get all of the environments for the project.
     */
    public function environments()
    {
        return $this->hasMany(Environment::class);
    }

    /**
     * Get all of the stacks for all environments.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function allStacks()
    {
        return $this->environments->flatMap->stacks;
    }

    /**
     * Get all of the certifiates assigned to stacks.
     *
     * @return \Illuminate\Support\Collection
     */
    public function activeCertificates()
    {
        return $this->allStacks()->map->certificate;
    }

    /**
     * Start provisioning a new database for the project.
     *
     * @param  string  $name
     * @param  string  $size
     * @return \App\Database
     */
    public function provisionDatabase($name, $size)
    {
        $id = $this->withProvider()->createServer($name, $size, $this->region);

        return tap($this->databases()->create([
            'name' => $name,
            'size' => $size,
            'provider_server_id' => $id,
            'sudo_password' => str_random(40),
            'username' => 'cloud',
            'password' => str_random(40),
            'allows_access_from' => [],
            'status' => 'creating',
        ]))->provision();
    }

    /**
     * Start provisioning a new balancer for the project.
     *
     * @param  string  $name
     * @param  string  $size
     * @param  bool  $selfSigns
     * @return \App\Balancer
     */
    public function provisionBalancer($name, $size, $selfSigns = false)
    {
        $id = $this->withProvider()->createServer($name, $size, $this->region);

        return tap($this->balancers()->create([
            'name' => $name,
            'size' => $size,
            'provider_server_id' => $id,
            'sudo_password' => str_random(40),
            'tls' => $selfSigns ? 'self-signed' : null,
            'status' => 'creating',
        ]))->provision();
    }

    /**
     * Get the provider gateway for the project.
     *
     * @return mixed
     */
    public function withProvider()
    {
        return ServerProviderClientFactory::make($this->serverProvider);
    }

    /**
     * Purge all of the project's resources.
     *
     * @return void
     */
    public function purge()
    {
        $this->databases->each->delete();
        $this->balancers->each->delete();
        $this->environments->each->delete();
    }

    /**
     * Archive the given project and mark it for deletion.
     *
     * @return void
     */
    public function archive()
    {
        $this->update([
            'archived' => true,
        ]);
    }
}
