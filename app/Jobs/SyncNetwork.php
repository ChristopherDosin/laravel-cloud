<?php

namespace App\Jobs;

use App\Database;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Scripts\SyncNetwork as SyncNetworkScript;

class SyncNetwork implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The database instance.
     *
     * @var \App\Database
     */
    public $database;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 20; // 5 Total Minutes...

    /**
     * Delete this job if any injected models are missing.
     *
     * @var bool
     */
    protected $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @param  \App\Database  $database
     * @return void
     */
    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->database->isProvisioned() ||
            ! $this->database->networkLock()->get()) {
            return $this->release(15);
        }

        $this->sync($this->database = $this->database->fresh());

        $this->database->update([
            'allows_access_from' => $this->database->shouldAllowAccessFrom(),
        ]);

        $this->database->networkLock()->release();
    }

    /**
     * Run the sync script for the given database and IP addresses.
     *
     * @param  \App\Database  $database
     * @return \App\Task
     */
    protected function sync(Database $database)
    {
        return $database->run(new SyncNetworkScript($database));
    }
}
