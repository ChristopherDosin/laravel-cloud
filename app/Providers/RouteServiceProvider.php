<?php

namespace App\Providers;

use App\Hook;
use App\Stack;
use App\Project;
use App\Database;
use App\Deployment;
use App\Environment;
use App\DatabaseBackup;
use App\SourceProvider;
use App\StorageProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();

        Route::model('sourceProvider', SourceProvider::class);
        Route::model('storageProvider', StorageProvider::class);
        Route::model('project', Project::class);
        Route::model('database', Database::class);
        Route::model('backup', DatabaseBackup::class);
        Route::model('environment', Environment::class);
        Route::model('stack', Stack::class);
        Route::model('deployment', Deployment::class);
        Route::model('hook', Hook::class);
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
        $this->mapScheduleRoutes();
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "schedule" routes for the application.
     *
     * @return void
     */
    protected function mapScheduleRoutes()
    {
        Route::namespace($this->namespace)
             ->group(base_path('routes/schedule.php'));
    }
}
