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

$factory->define(App\ServerProvider::class, function () {
    return [
        'user_id' => function () {
            return factory(App\User::class)->create();
        },
        'name' => 'DigitalOcean',
        'type' => 'DigitalOcean',
        'meta' => ['token' => config('services.testing.digital_ocean')],
    ];
});
