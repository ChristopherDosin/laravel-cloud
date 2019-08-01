<?php

namespace App;

use Carbon\Carbon;
use App\Jobs\Build;
use App\Jobs\Activate;
use App\Jobs\MonitorDeployment;
use App\Events\DeploymentFailed;
use App\Events\DeploymentBuilding;
use App\Events\DeploymentFinished;
use App\Events\DeploymentTimedOut;
use App\Events\DeploymentCancelled;
use App\Events\DeploymentActivating;
use Illuminate\Database\Eloquent\Model;
use App\Jobs\TimeOutDeploymentIfStillRunning;

class Deployment extends Model
{
    use DeterminesAge;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'activated' => 'boolean',
        'build_commands' => 'json',
        'activation_commands' => 'json',
        'directories' => 'json',
        'daemons' => 'json',
        'schedule' => 'json',
        'meta' => 'json',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the project for the deploymnet.
     *
     * @return \App\Project
     */
    public function project()
    {
        return $this->stack->environment->project;
    }

    /**
     * Get the source control provider for the deploymnet.
     *
     * @return \App\SourceProvider
     */
    public function sourceProvider()
    {
        return $this->project()->sourceProvider;
    }

    /**
     * Get the repository for the deployment.
     *
     * @return string
     */
    public function repository()
    {
        return $this->project()->repository;
    }

    /**
     * Determine if the deployment is for a production environment.
     *
     * @return bool
     */
    public function isProduction()
    {
        return $this->stack->environment->isProduction();
    }

    /**
     * Get the stack the deployment belongs to.
     */
    public function stack()
    {
        return $this->belongsTo(Stack::class, 'stack_id');
    }

    /**
     * Get the user who initiated the deployment, if applicable.
     */
    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    /**
     * Get all of the individual server deployments.
     */
    public function serverDeployments()
    {
        return $this->hasMany(ServerDeployment::class);
    }

    /**
     * Get the tarball URL for the deployment.
     *
     * @return string
     */
    public function hash()
    {
        return $this->commit_hash;
    }

    /**
     * Get the tarball URL for the deployment.
     *
     * @return string
     */
    public function tarballUrl()
    {
        return $this->sourceProvider()->client()->tarballUrl($this);
    }

    /**
     * Get the UNIX timestamp of the deployment's creation date.
     *
     * @return int
     */
    public function timestamp()
    {
        return $this->created_at->getTimestamp();
    }

    /**
     * Start monitoring this deployment.
     *
     * @return void
     */
    public function monitor()
    {
        MonitorDeployment::dispatch($this);

        TimeOutDeploymentIfStillRunning::dispatch($this)->delay(
            Carbon::now()->addMinutes(40)
        );
    }

    /**
     * Determine if the deployment is still pending.
     *
     * @return bool
     */
    public function isPending()
    {
        return $this->status == 'pending';
    }

    /**
     * Determine if all of the server deployments have built.
     *
     * @return bool
     */
    public function isBuilt()
    {
        return $this->serverDeployments->every->isBuilt();
    }

    /**
     * Determine if any of the server deployments are still building.
     *
     * @return bool
     */
    public function isBuilding()
    {
        return $this->serverDeployments->contains->isBuilding();
    }

    /**
     * Build a fresh release of the project.
     *
     * @return void
     */
    public function build()
    {
        $this->update(['status' => 'building']);

        $this->createServerDeployments()->each->build();

        DeploymentBuilding::dispatch($this);
    }

    /**
     * Create the server deployments for this deployment.
     *
     * @return void
     */
    protected function createServerDeployments()
    {
        return $this->stack->allServers()->map(function ($server) {
            return $this->serverDeployments()->create([
                'deployable_id' => $server->id,
                'deployable_type' => get_class($server),
                'build_commands' => $this->buildCommandsFor($server)->all(),
                'activation_commands' => $this->activationCommandsFor($server)->all(),
                'status' => 'building',
            ]);
        });
    }

    /**
     * Get all of the build commands as a collection of objects.
     *
     * @return \Illuminate\Support\Collection
     */
    public function buildCommands()
    {
        return collect($this->build_commands)->mapInto(ShellCommand::class);
    }

    /**
     * Get the build commands for the given server.
     *
     * @param  \App\Server  $server
     * @return \Illuminate\Support\Collection
     */
    protected function buildCommandsFor($server)
    {
        return $this->buildCommands()->filter->appliesTo($server)->reject->prefixed(
            ! $server->isMaster() ? 'once:' : null
        )->map->trim()->values();
    }

    /**
     * Get all of the activation commands as a collection of objects.
     *
     * @return \Illuminate\Support\Collection
     */
    public function activationCommands()
    {
        return collect($this->activation_commands)->mapInto(ShellCommand::class);
    }

    /**
     * Get the activation commands for the given server.
     *
     * @param  \App\Server  $server
     * @return \Illuminate\Support\Collection
     */
    protected function activationCommandsFor($server)
    {
        return $this->activationCommands()->filter->appliesTo($server)->reject->prefixed(
            ! $server->isMaster() ? 'once:' : null
        )->map->trim()->values();
    }

    /**
     * Determine if all of the server deployments have activated.
     *
     * @return bool
     */
    public function isActivated()
    {
        return $this->serverDeployments->count() > 0 &&
               $this->serverDeployments->every->isActivated();
    }

    /**
     * Determine if the deployment is activating.
     *
     * @return bool
     */
    public function isActivating()
    {
        return $this->status == 'activating';
    }

    /**
     * Activate the deployed code across all servers.
     *
     * @return void
     */
    public function activate()
    {
        $this->update([
            'activated' => true,
            'status' => 'activating',
        ]);

        $this->serverDeployments->each->activate();

        DeploymentActivating::dispatch($this);
    }

    /**
     * Determine if the deployment is "finished".
     *
     * @return bool
     */
    public function isFinished()
    {
        return $this->status == 'finished';
    }

    /**
     * Mark the deployment as finished.
     *
     * @return void
     */
    public function markAsFinished()
    {
        $this->update(['status' => 'finished']);

        DeploymentFinished::dispatch($this);
    }

    /**
     * Determine if the deployment has been marked as timed out.
     *
     * @return bool
     */
    public function isTimedOut()
    {
        return $this->status == 'timeout';
    }

    /**
     * Mark the deployment as timed out.
     *
     * @return void
     */
    public function markAsTimedOut()
    {
        $this->update(['status' => 'timeout']);

        DeploymentTimedOut::dispatch($this);
    }

    /**
     * Determine if the deployment has been marked as failed.
     *
     * @return bool
     */
    public function hasFailed()
    {
        return $this->status == 'failed';
    }

    /**
     * Determine if the deployment has any failed server deployments.
     *
     * @return bool
     */
    public function hasFailures()
    {
        return $this->serverDeployments->filter->hasFailed()->isNotEmpty();
    }

    /**
     * Mark the deployment as failed.
     *
     * @param  \Exception|null  $exception
     * @return void
     */
    public function markAsFailed($exception = null)
    {
        $this->update(['status' => 'failed']);

        DeploymentFailed::dispatch($this, $exception);
    }

    /**
     * Determine if the deployment has been cancelled.
     *
     * @return bool
     */
    public function wasCancelled()
    {
        return $this->status == 'cancelled';
    }

    /**
     * Cancel the deployment.
     *
     * @return bool
     */
    public function cancel()
    {
        if ($this->hasEnded() || $this->isActivating()) {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
        ]);

        DeploymentCancelled::dispatch($this);

        return true;
    }

    /**
     * Determine if the deployment has completed in some way.
     *
     * @return bool
     */
    public function hasEnded()
    {
        return $this->isActivated() ||
               $this->isTimedOut() ||
               $this->hasFailed() ||
               $this->wasCancelled();
    }
}
