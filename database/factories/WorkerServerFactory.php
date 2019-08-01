<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(App\WorkerServer::class, function () {
    return [
        'project_id' => factory(App\Project::class),
        'stack_id' => factory(App\Stack::class),
        'name' => str_random(6),
        'size' => '2gb',
        'provider_server_id' => 1,
        'port' => 22,
        'sudo_password' => str_random(40),
        'meta' => [],
        'status' => 'provisioned',
        'daemon_status' => 'pending',
    ];
});
