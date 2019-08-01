<?php

namespace App\Jobs;

use App\StorageProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DeleteDatabaseBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The storage provider instance.
     *
     * @var \App\StorageProvider
     */
    public $provider;

    /**
     * The database backup path.
     *
     * @var string
     */
    public $backupPath;

    /**
     * Create a new job instance.
     *
     * @param  \App\StorageProvider  $provider
     * @param  string  $backupPath
     * @return void
     */
    public function __construct(StorageProvider $provider, $backupPath)
    {
        $this->provider = $provider;
        $this->backupPath = $backupPath;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->provider->client()->delete(
            $this->backupPath
        );
    }
}
