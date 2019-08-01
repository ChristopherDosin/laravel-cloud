<?php

namespace App;

use Symfony\Component\Process\Process;

class SecureShellKey
{
    /**
     * Create a new SSH key for a new user.
     *
     * @param  string  $password
     * @return object
     */
    public static function forNewUser($password = '')
    {
        return app()->environment(/*'local',*/ 'testing')
                        ? static::forTesting()
                        : static::make($password);
    }

    /**
     * Create a new SSH key for testing.
     *
     * @return object
     */
    protected static function forTesting()
    {
        return (object) [
            'publicKey' => file_get_contents(env('TEST_SSH_CONTAINER_PUBLIC_KEY')),
            'privateKey' => file_get_contents(env('TEST_SSH_CONTAINER_KEY')),
        ];
    }

    /**
     * Create a new SSH key.
     *
     * @param  string  $password
     * @return object
     */
    public static function make($password = '')
    {
        $name = str_random(20);

        (new Process(
            "ssh-keygen -C \"robot@laravel.com\" -f {$name} -t rsa -b 4096 -N ".escapeshellarg($password),
            storage_path('app')
        ))->run();

        [$publicKey, $privateKey] = [
            file_get_contents(storage_path('app/'.$name.'.pub')),
            file_get_contents(storage_path('app/'.$name)),
        ];

        @unlink(storage_path('app/'.$name.'.pub'));
        @unlink(storage_path('app/'.$name));

        return (object) compact('publicKey', 'privateKey');
    }

    /**
     * Store a secure shell key for the given user.
     *
     * @param  \App\User  $user
     * @return string
     */
    public static function storeFor(User $user)
    {
        return tap(storage_path('app/keys/'.$user->id), function ($path) use ($user) {
            static::ensureKeyDirectoryExists();

            static::ensureFileExists($path, $user->private_worker_key, 0600);
        });
    }

    /**
     * Ensure the SSH key directory exists.
     *
     * @return void
     */
    protected static function ensureKeyDirectoryExists()
    {
        if (! is_dir(storage_path('app/keys'))) {
            mkdir(storage_path('app/keys'), 0755, true);
        }
    }

    /**
     * Ensure the given file exists.
     *
     * @param  string  $path
     * @param  string  $contents
     * @param  string  $chmod
     * @return string
     */
    protected static function ensureFileExists($path, $contents, $chmod)
    {
        file_put_contents($path, $contents);

        chmod($path, $chmod);
    }
}
