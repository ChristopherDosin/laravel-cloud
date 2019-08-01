<?php

namespace App\Jobs;

use App\DatabaseRestore;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use App\Callbacks\CheckDatabaseRestore;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Scripts\RestoreDatabaseBackup as RestoreDatabaseBackupScript;

class RestoreDatabaseBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The database restore instance.
     *
     * @var \App\DatabaseRestore
     */
    public $restore;

    /**
     * Create a new job instance.
     *
     * @param  \App\DatabaseRestore  $restore
     * @return void
     */
    public function __construct(DatabaseRestore $restore)
    {
        $this->restore = $restore;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->restore->markAsRunning();

        $this->restore->database->runInBackground(new RestoreDatabaseBackupScript($this->restore), [
            'then' => [
                new CheckDatabaseRestore($this->restore->id),
            ],
        ]);
    }
}
