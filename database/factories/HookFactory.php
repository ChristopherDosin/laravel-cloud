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

$factory->define(App\Hook::class, function () {
    return [
        'stack_id' => factory(App\Stack::class),
        'name' => 'Test Hook',
        'branch' => 'master',
        'token' => str_random(40),
        'meta' => [],
        'published' => false,
    ];
});
