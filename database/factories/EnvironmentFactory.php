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

$factory->define(App\Environment::class, function () {
    return [
        'project_id' => factory(App\Project::class),
        'creator_id' => factory(App\User::class),
        'name' => 'production',
        'encryption_key' => '',
        'variables' => '',
    ];
});
