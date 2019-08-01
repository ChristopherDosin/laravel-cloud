<?php

namespace App;

use App\Jobs\StopScheduler;
use App\Jobs\StartScheduler;
use App\Jobs\DeleteServerOnProvider;
use App\Callbacks\MarkAsProvisioned;
use Illuminate\Database\Eloquent\Model;
use App\Contracts\Provisionable as ProvisionableContract;

abstract class Server extends Model implements ProvisionableContract
{
    use Provisionable;

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
        'private_key', 'sudo_password',
    ];

    /**
     * Get the provisioning script for the server.
     *
     * @return \App\Scripts\Script
     */
    abstract public function provisioningScript();

    /**
     * Determine if this server will run a given deployment command.
     *
     * @param  string  $command
     * @return bool
     */
    abstract public function runsCommand($command);

    /**
     * Get the stack the server belongs to.
     */
    public function stack()
    {
        return $this->belongsTo(Stack::class, 'stack_id');
    }

    /**
     * Get all of the deployments to the server.
     */
    public function deployments()
    {
        return $this->morphMany(ServerDeployment::class, 'deployable')->latest('id');
    }

    /**
     * Get the last deployment for the server.
     *
     * @return \App\ServerDeployment|null
     */
    public function lastDeployment()
    {
        return $this->deployments->first();
    }

    /**
     * Determine if this server is the "master" server for the stack.
     *
     * @return bool
     */
    public function isMaster()
    {
        return false;
    }

    /**
     * Determine if this server processes queued jobs.
     *
     * @return bool
     */
    public function isWorker()
    {
        return false;
    }

    /**
     * Determine if this server is an actual WorkerServer.
     *
     * @return bool
     */
    public function isTrueWorker()
    {
        return $this instanceof WorkerServer;
    }

    /**
     * Determine if this server is the "master" worker for the stack.
     *
     * @return bool
     */
    public function isMasterWorker()
    {
        return false;
    }

    /**
     * Get the recommended balancer size for the server.
     *
     * @return string
     */
    public function recommendedBalancerSize()
    {
        return $this->stack->environment
                        ->project
                        ->withProvider()
                        ->recommendedBalancerSize($this->size);
    }

    /**
     * Determine if the given user can SSH into the server.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function canSsh(User $user)
    {
        return $user->canAccessProject($this->project);
    }

    /**
     * Enable the background services for the server.
     *
     * @return void
     */
    public function startBackgroundServices()
    {
        if ($this->isWorker() && $this->lastDeployment()) {
            $this->lastDeployment()->startScheduler();

            $this->lastDeployment()->restartDaemons();
        }
    }

    /**
     * Disable the background services for the stack.
     *
     * @return void
     */
    public function stopBackgroundServices()
    {
        if ($this->isWorker() && $this->lastDeployment()) {
            $this->lastDeployment()->stopScheduler();

            $this->lastDeployment()->stopDaemons();
        }
    }

    /**
     * Get all of the daemon generations for the server.
     */
    public function daemonGenerations()
    {
        return $this->morphMany(
            DaemonGeneration::class, 'generationable'
        )->latest('id');
    }

    /**
     * Create a fresh daemon generation.
     *
     * @return \App\DaemonGeneration
     */
    public function createDaemonGeneration()
    {
        return tap($this->daemonGenerations()->create([]), function () {
            $this->trimDaemonGenerations();
        });
    }

    /**
     * Trim the daemon generations for the server.
     *
     * @return void
     */
    protected function trimDaemonGenerations()
    {
        $generations = $this->daemonGenerations()->get();

        if (count($generations) > 10) {
            $generations->slice(10 - count($generations))->each->delete();
        }
    }

    /**
     * Determine if the server's daemons are pending.
     *
     * @return bool
     */
    public function daemonsArePending()
    {
        return $this->daemon_status === 'pending';
    }

    /**
     * Determine if the server's daemons are running.
     *
     * @return bool
     */
    public function daemonsAreRunning()
    {
        return $this->daemon_status === 'running';
    }

    /**
     * Mark the stack's daemons as stopped.
     *
     * @return $this
     */
    public function markDaemonsAsRunning()
    {
        return tap($this)->update([
            'daemon_status' => 'running',
        ]);
    }

    /**
     * Mark the stack's daemons as stopped.
     *
     * @return $this
     */
    public function markDaemonsAsStopped()
    {
        return tap($this)->update([
            'daemon_status' => 'stopped',
        ]);
    }

    /**
     * Run the provisioning script on the server.
     *
     * @return \App\Task|null
     */
    public function runProvisioningScript()
    {
        if (! $this->isProvisioning()) {
            $this->markAsProvisioning();

            return $this->runInBackground($this->provisioningScript(), [
                'then' => [
                    MarkAsProvisioned::class,
                ],
            ]);
        }
    }

    /**
     * Delete the model from the database.
     *
     * @return bool|null
     *
     * @throws \Exception
     */
    public function delete()
    {
        $this->deleteOnProvider();

        $this->address()->delete();
        $this->daemonGenerations()->delete();
        $this->tasks()->delete();

        parent::delete();
    }

    /**
     * Delete the server on the provider.
     *
     * @return void
     */
    protected function deleteOnProvider()
    {
        if (! $this->providerServerId()) {
            return;
        }

        DeleteServerOnProvider::dispatch(
            $this->project, $this->providerServerId()
        );
    }
}
