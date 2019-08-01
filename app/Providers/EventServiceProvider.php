<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\AlertCreated' => [
            'App\Listeners\UpdateLastAlertTimestampForCollaborators',
            'App\Listeners\TrimAlertsForProject',
        ],

        'App\Events\DatabaseBackupRunning' => [
            //
        ],

        'App\Events\DatabaseBackupFinished' => [
            //
        ],

        'App\Events\DatabaseBackupFailed' => [
            //
        ],

        'App\Events\DatabaseRestoreRunning' => [
            //
        ],

        'App\Events\DatabaseRestoreFinished' => [
            //
        ],

        'App\Events\DatabaseRestoreFailed' => [
            //
        ],

        'App\Events\ProjectShared' => [
            //
        ],

        'App\Events\ProjectUnshared' => [
            //
        ],

        'App\Events\StackProvisioning' => [
            //
        ],

        'App\Events\StackProvisioned' => [
            'App\Listeners\CreateAlert'
        ],

        'App\Events\StackDeleting' => [
            //
        ],

        'App\Events\DeploymentBuilding' => [
            //
        ],

        'App\Events\DeploymentActivating' => [
            //
        ],

        'App\Events\DeploymentFinished' => [
            'App\Listeners\ResetDeploymentStatus',
            'App\Listeners\CreateAlert',
            'App\Listeners\CheckPendingDeployments',
        ],

        'App\Events\DeploymentTimedOut' => [
            'App\Listeners\ResetDeploymentStatus',
            'App\Listeners\CreateAlert',
        ],

        'App\Events\DeploymentFailed' => [
            'App\Listeners\ResetDeploymentStatus',
            'App\Listeners\CreateAlert',
        ],

        'App\Events\DeploymentCancelled' => [
            'App\Listeners\ResetDeploymentStatus',
            'App\Listeners\CreateAlert',
        ],

        'App\Events\ServerDeploymentBuilt' => [
            //
        ],

        'App\Events\ServerDeploymentActivated' => [
            //
        ],

        'App\Events\ServerDeploymentFailed' => [
            //
        ],

        'App\Events\StackTaskRunning' => [
            //
        ],

        'App\Events\StackTaskFinished' => [
            //
        ],

        'App\Events\StackTaskFailed' => [
            //
        ],

        'App\Events\ServerTaskFinished' => [
            //
        ],

        'App\Events\ServerTaskFailed' => [
            //
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
