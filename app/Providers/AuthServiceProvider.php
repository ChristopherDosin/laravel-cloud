<?php

namespace App\Providers;

use Laravel\Passport\Passport;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Balancer' => 'App\Policies\BalancerPolicy',
        'App\Database' => 'App\Policies\DatabasePolicy',
        'App\DatabaseBackup' => 'App\Policies\DatabaseBackupPolicy',
        'App\DatabaseRestore' => 'App\Policies\DatabaseRestorePolicy',
        'App\Project' => 'App\Policies\ProjectPolicy',
        'App\Environment' => 'App\Policies\EnvironmentPolicy',
        'App\Stack' => 'App\Policies\StackPolicy',
        'App\AppServer' => 'App\Policies\AppServerPolicy',
        'App\WebServer' => 'App\Policies\WebServerPolicy',
        'App\WorkerServer' => 'App\Policies\WorkerServerPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Passport::routes();
    }
}
