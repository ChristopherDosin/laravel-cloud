<?php

namespace Tests;

use App\User;
use App\SecureShellKey;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * The previous exception handler.
     */
    protected $previousExceptionHandler;

    /**
     * Setup the test class.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        Mail::fake();
    }

    /**
     * Create a dummy user.
     *
     * @return \App\User
     */
    protected function user()
    {
        return factory(User::class)->create();
    }

    /**
     * Refresh the SSH keys on the user instance.
     *
     * @param  \App\User  $user
     * @return \App\User
     */
    protected function refreshKeys($user)
    {
        return tap($user)->update([
            'keypair' => SecureShellKey::make(),
            'worker_keypair' => SecureShellKey::make(),
        ]);
    }
}
