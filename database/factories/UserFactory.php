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

$factory->define(App\User::class, function ($faker) {
    static $key;
    static $password;
    static $workerKey;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
        'keypair' => $key = $key ?: App\SecureShellKey::forNewUser(),
        'worker_keypair' => $workerKey = $workerKey ?: App\SecureShellKey::forNewUser(),
    ];
});
