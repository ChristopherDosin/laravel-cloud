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


$factory->define(App\StorageProvider::class, function () {
    return [
        'user_id' => factory(App\User::class),
        'name' => 'S3',
        'type' => 'S3',
        'meta' => [
            'key' => config('services.testing.s3_key'),
            'secret' => config('services.testing.s3_secret'),
            'region' => 'us-east-1',
            'bucket' => 'laravel-cloud-test',
        ],
    ];
});
