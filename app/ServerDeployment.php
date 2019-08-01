<?php

namespace App;

use App\Jobs\Build;
use App\Jobs\Activate;
use App\Jobs\StopDaemons;
use App\Jobs\PauseDaemons;
use App\Jobs\StartDaemons;
use App\Jobs\StopScheduler;
use App\Jobs\RestartDaemons;
use App\Jobs\StartScheduler;
use App\Jobs\UnpauseDaemons;
use App\Events\ServerDeploymentBuilt;
use App\Events\ServerDeploymentFailed;
use Illuminate\Database\Eloquent\Model;
use App\Events\ServerDeploymentActivated;

class ServerDeployment extends Model
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'build_commands' => 'json',
        'activation_commands' => 'json',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the project for the server deployment.
     *
     * @return \App\Project
     */
    public function project()
    {
        return $this->deployment->project();
    }

    /**
     * Get the stack for the server deployment.
     *
     * @return \App\Stack
     */
    public function stack()
    {
        return $this->deployment->stack;
    }

    /**
     * Get the environment variables for the deployment's environment.
     *
     * @return string
     */
    public function environmentVariables()
    {
        return trim($this->stack()->environment->variables);
    }

    /**
     * Get the database host for the deployment.
     *
     * @return string|null
     */
    public function databaseHost()
    {
        if (count($this->stack()->databases) === 1) {
            return $this->stack()->databases[0]->address->private_address;
        }

        if ($this->deployable instanceof AppServer) {
            return '127.0.0.1';
        }
    }

    /**
     * Get the database password for the deployment.
     *
     * @return string|null
     */
    public function databasePassword()
    {
        if (count($this->stack()->databases) === 1) {
            return $this->stack()->databases[0]->password;
        }

        if ($this->deployable instanceof AppServer) {
            return $this->deployable->database_password;
        }
    }

    /**
     * Get the deployment the server deployment belongs to.
     */
    public function deployment()
    {
        return $this->belongsTo(Deployment::class, 'deployment_id');
    }

    /**
     * Determine if the deployment is for a production environment.
     *
     * @return bool
     */
    public function isProduction()
    {
        return $this->deployment->isProduction();
    }

    /**
     * Get the deployable server for this server deployment.
     */
    public function deployable()
    {
        return $this->morphTo();
    }

    /**
     * Get the task associated with the build.
     */
    public function buildTask()
    {
        return $this->belongsTo(Task::class, 'build_task_id');
    }

    /**
     * Get the task associated with the build.
     */
    public function activationTask()
    {
        return $this->belongsTo(Task::class, 'activation_task_id');
    }

    /**
     * Get the PHP version for the stack that owns the deployment.
     *
     * @return string
     */
    public function phpVersion()
    {
        return $this->stack()->phpVersion();
    }

    /**
     * Get the tarball URL for the deployment.
     *
     * @return string
     */
    public function hash()
    {
        return $this->deployment->hash();
    }

    /**
     * Get the tarball URL for the deployment.
     *
     * @return string
     */
    public function tarballUrl()
    {
        return $this->deployment->tarballUrl();
    }

    /**
     * Get the UNIX timestamp of the deployment's creation date.
     *
     * @return int
     */
    public function timestamp()
    {
        return $this->deployment->timestamp();
    }

    /**
     * Determine if the deployment is building.
     *
     * @return bool
     */
    public function isBuilding()
    {
        return $this->status == 'building';
    }

    /**
     * Build the deployment.
     *
     * @return void
     */
    public function build()
    {
        Build::dispatch($this);
    }

    /**
     * Determine if the deployment has finished building.
     *
     * @return bool
     */
    public function isBuilt()
    {
        return $this->status == 'built';
    }

    /**
     * Mark the server deployment as built.
     *
     * @return void
     */
    public function markAsBuilt()
    {
        $this->update(['status' => 'built']);

        ServerDeploymentBuilt::dispatch($this);
    }

    /**
     * Activate the deployment.
     *
     * @return void
     */
    public function activate()
    {
        $this->markAsActivating();

        Activate::dispatch($this);
    }

    /**
     * Mark the server deployment as activating.
     *
     * @return void
     */
    public function markAsActivating()
    {
        $this->update(['status' => 'activating']);
    }

    /**
     * Determine if the deployment has finished activating.
     *
     * @return bool
     */
    public function isActivated()
    {
        return $this->status == 'activated';
    }

    /**
     * Mark the server deployment as activated.
     *
     * @return void
     */
    public function markAsActivated()
    {
        $this->update(['status' => 'activated']);

        ServerDeploymentActivated::dispatch($this);
    }

    /**
     * Get the current daemon generation.
     *
     * @return \App\DaemonGeneration
     */
    public function currentDaemonGeneration()
    {
        return $this->deployable->daemonGenerations->first();
    }

    /**
     * Get the previous daemon generations.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function previousDaemonGenerations()
    {
        return $this->deployable->daemonGenerations->slice(1);
    }

    /**
     * Start the daemons defined for a given deployment.
     *
     * @return void
     */
    public function startDaemons()
    {
        if (empty($this->daemons())) {
            return;
        }

        $this->deployable->markDaemonsAsRunning();

        StartDaemons::dispatch($this);
    }

    /**
     * Restart the daemons defined for a given deployment.
     *
     * @return void
     */
    public function restartDaemons()
    {
        if (empty($this->daemons())) {
            return;
        }

        $this->deployable->markDaemonsAsRunning();

        RestartDaemons::dispatch($this);
    }

    /**
     * Pause the daemons defined for a given deployment.
     *
     * @return void
     */
    public function pauseDaemons()
    {
        if (empty($this->daemons())) {
            return;
        }

        PauseDaemons::dispatch($this);
    }

    /**
     * Unpause the daemons defined for a given deployment.
     *
     * @return void
     */
    public function unpauseDaemons()
    {
        if (empty($this->daemons())) {
            return;
        }

        $this->deployable->markDaemonsAsRunning();

        UnpauseDaemons::dispatch($this);
    }

    /**
     * Stop the daemons defined for a given deployment.
     *
     * @return void
     */
    public function stopDaemons()
    {
        if (empty($this->daemons())) {
            return;
        }

        $this->deployable->markDaemonsAsStopped();

        StopDaemons::dispatch($this);
    }

    /**
     * Get the daemons for the deployment.
     *
     * @return array
     */
    public function daemons()
    {
        return $this->deployment->daemons;
    }

    /**
     * Start the scheduler for the server.
     *
     * @return void
     */
    public function startScheduler()
    {
        if (! empty($this->schedule())) {
            StartScheduler::dispatch($this);
        }
    }

    /**
     * Stop the scheduler for the server.
     *
     * @return void
     */
    public function stopScheduler()
    {
        if (! empty($this->schedule())) {
            StopScheduler::dispatch($this);
        }
    }

    /**
     * Get the scheduled tasks for the deployment.
     *
     * @return array
     */
    public function schedule()
    {
        return $this->deployment->schedule;
    }

    /**
     * Determine if the deployment has failed.
     *
     * @return bool
     */
    public function hasFailed()
    {
        return $this->status == 'failed';
    }

    /**
     * Mark the server deployment as failed.
     *
     * @return void
     */
    public function markAsFailed()
    {
        $this->update(['status' => 'failed']);

        ServerDeploymentFailed::dispatch($this);
    }
}
