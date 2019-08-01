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

$factory->define(App\DatabaseBackup::class, function () {
    return [
        'database_id' => factory(App\Database::class),
        'storage_provider_id' => factory(App\StorageProvider::class),
        'database_name' => 'Test Database',
        'backup_path' => 'laravel-cloud-test/backups/laravel/2017-01-01-12-01-01.sql.gz',
        'status' => 'running',
        'output' => '',
    ];
});
