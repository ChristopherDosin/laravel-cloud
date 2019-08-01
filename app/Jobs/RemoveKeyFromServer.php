<?php

namespace App\Jobs;

use App\User;
use Illuminate\Bus\Queueable;
use App\Contracts\Provisionable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Scripts\RemoveKeyFromServer as RemoveKeyFromServerScript;

class RemoveKeyFromServer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The user instance.
     *
     * @var \App\User
     */
    public $user;

    /**
     * The provisionable implementation.
     *
     * @var \App\Contracts\Provisionable
     */
    public $provisionable;

    /**
     * Delete this job if any injected models are missing.
     *
     * @var bool
     */
    protected $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @param  \App\User  $user
     * @param  \App\Contracts\Provisionable  $provisionable
     * @return void
     */
    public function __construct(User $user, Provisionable $provisionable)
    {
        $this->user = $user;
        $this->provisionable = $provisionable;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->provisionable->run(new RemoveKeyFromServerScript(
            'cloud-user-'.$this->user->id
        ));
    }
}
