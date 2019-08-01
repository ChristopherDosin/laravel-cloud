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

$factory->define(App\Deployment::class, function () {
    return [
        'stack_id' => factory(App\Stack::class),
        'branch' => 'master',
        'commit_hash' => str_random(20),
        'build_commands' => ['first'],
        'activation_commands' => ['second'],
        'directories' => ['storage'],
        'daemons' => [],
        'schedule' => [],
        'status' => 'pending',
    ];
});
