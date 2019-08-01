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

$factory->define(App\Stack::class, function () {
    return [
        'environment_id' => factory(App\Environment::class),
        'creator_id' => factory(App\User::class),
        'name' => 'test-stack',
        'url' => App\Haiku::withToken(),
        'balanced' => false,
        'status' => 'pending',
        'pending_deployment' => [],
        'meta' => [
            'php' => '7.1',
            'initial_branch' => 'master',
            'initial_build_commands' => [],
            'initial_activation_commands' => [],
        ],
    ];
});
