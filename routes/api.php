<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login', 'API\LoginController@handle');

// Task Callbacks...
Route::get('/callback/{hashid}', 'API\CallbackController@handle');

// Hook Deployments...
Route::post('/hook-deployment/{hook}/{token}', 'API\HookDeploymentController@store');

Route::group([
    'middleware' => 'auth:api',
], function () {
    // Providers...
    Route::get('/server-providers', 'API\ServerProviderController@index');
    Route::post('/server-provider', 'API\ServerProviderController@store');

    Route::get('/server-provider/{provider}/regions', 'API\ServerProviderRegionController@index');
    Route::get('/server-provider/{provider}/sizes', 'API\ServerProviderSizeController@index');

    // Source Control Providers...
    Route::get('/source-providers', 'API\SourceProviderController@index');
    Route::post('/source-provider', 'API\SourceProviderController@store');
    Route::delete('/source-provider/{sourceProvider}', 'API\SourceProviderController@destroy');

    // Storage Providers...
    Route::get('/storage-providers', 'API\StorageProviderController@index');
    Route::post('/storage-provider', 'API\StorageProviderController@store');
    Route::delete('/storage-provider/{storageProvider}', 'API\StorageProviderController@destroy');

    // Projects...
    Route::get('/projects', 'API\ProjectController@index');
    Route::get('/owned-projects', 'API\OwnedProjectsController@index');
    Route::get('/project/{project}', 'API\ProjectController@show');
    Route::post('/project', 'API\ProjectController@store');
    Route::get('/project/{project}/sizes', 'API\ProjectSizeController@index');
    Route::delete('/project/{project}', 'API\ProjectController@destroy');

    // Project Collaborators...
    Route::get('/collaborators', 'API\CollaboratorController@index');
    Route::delete('/collaborator', 'API\CollaboratorController@destroy');

    Route::get('/project/{project}/collaborators', 'API\ProjectCollaboratorController@index');
    Route::post('/project/{project}/collaborator', 'API\ProjectCollaboratorController@store');
    Route::delete('/project/{project}/collaborator', 'API\ProjectCollaboratorController@destroy');

    // Databases...
    Route::get('/project/{project}/databases', 'API\DatabaseController@index');
    Route::get('/project/{project}/ssh-databases', 'API\SshDatabaseController@index');
    Route::post('/project/{project}/database', 'API\DatabaseController@store');
    Route::post('/database/{database}/transfers', 'API\DatabaseTransferController@store');
    Route::delete('/database/{database}', 'API\DatabaseController@destroy');

    // Database Backups...
    Route::get('/database/{database}/backups', 'API\DatabaseBackupController@index');
    Route::post('/database/{database}/backup', 'API\DatabaseBackupController@store');
    Route::delete('/backup/{backup}', 'API\DatabaseBackupController@destroy');

    // Database Restores...
    Route::get('/database/{database}/restores', 'API\DatabaseRestoreController@index');
    Route::post('/backup/{backup}/restore', 'API\DatabaseRestoreController@store');

    // Balancers...
    Route::get('/project/{project}/balancers', 'API\BalancerController@index');
    Route::get('/project/{project}/ssh-balancers', 'API\SshBalancerController@index');
    Route::post('/project/{project}/balancer', 'API\BalancerController@store');
    Route::delete('/balancer/{balancer}', 'API\BalancerController@destroy');

    // Environments...
    Route::get('/project/{project}/environments', 'API\EnvironmentController@index');
    Route::get('/environment/{environment}', 'API\EnvironmentController@show');
    Route::post('/project/{project}/environment', 'API\EnvironmentController@store');
    Route::put('/environment/{environment}', 'API\EnvironmentController@update');
    Route::delete('/environment/{environment}', 'API\EnvironmentController@destroy');

    // Stacks...
    Route::get('/project/{project}/stacks', 'API\StackController@index');
    Route::post('/environment/{environment}/stack', 'API\StackController@store');
    Route::get('/environment/{environment}/promoted-stack', 'API\PromotedStackController@show');
    Route::put('/environment/{environment}/promoted-stack', 'API\PromotedStackController@update');
    Route::delete('/stacks/{stack}', 'API\StackController@destroy');

    Route::put('/stack/{stack}/server-configuration', 'API\ServerConfigurationController@update');
    Route::put('/stack/{stack}/databases', 'API\StackDatabaseController@update');

    // Stack Servers...
    Route::get('/stack/{stack}/servers', 'API\StackServerController@index');
    Route::get('/stack/{stack}/ssh-servers', 'API\StackSshServerController@index');

    // Deployments...
    Route::get('/stack/{stack}/deployments', 'API\DeploymentController@index');
    Route::get('/deployment/{deployment}', 'API\DeploymentController@show');
    Route::post('/stack/{stack}/deployment', 'API\DeploymentController@store');
    Route::delete('/stack/{stack}/deployment', 'API\LastDeploymentController@destroy');
    Route::delete('/deployment/{deployment}', 'API\DeploymentController@destroy');

    // Hooks...
    Route::get('/environment/{environment}/hooks', 'API\EnvironmentHookController@index');
    Route::get('/stack/{stack}/hooks', 'API\HookController@index');
    Route::post('/stack/{stack}/hook', 'API\HookController@store');
    Route::delete('/hook/{hook}', 'API\HookController@destroy');

    // Daemons...
    Route::put('/stack/{stack}/daemons', 'API\DaemonController@update');

    // Scheduler...
    Route::post('/stack/{stack}/scheduler', 'API\SchedulerController@store');
    Route::delete('/stack/{stack}/scheduler', 'API\SchedulerController@destroy');

    // Keys...
    Route::post('/key/{ip_address}', 'API\KeyController@store');

    // Stack Tasks...
    Route::post('/stack/{stack}/stack-tasks', 'API\StackTaskController@store');

    // Maintenance Mode...
    Route::post('/maintenanced-stacks', 'API\MaintenancedStackController@store');
    Route::delete('/maintenanced-stack/{stack}', 'API\MaintenancedStackController@destroy');
});
