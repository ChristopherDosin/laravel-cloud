<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Jobs\DeleteDatabaseBackup;
use App\Jobs\RestoreDatabaseBackup;
use App\Events\DatabaseBackupFailed;
use App\Events\DatabaseBackupRunning;
use App\Events\DatabaseBackupFinished;
use Illuminate\Database\Eloquent\Model;

class DatabaseBackup extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'output',
    ];

    /**
     * The database the backup belongs to.
     */
    public function database()
    {
        return $this->belongsTo(Database::class, 'database_id');
    }

    /**
     * Get the storage provider used for the backup.
     */
    public function storageProvider()
    {
        return $this->belongsTo(StorageProvider::class, 'storage_provider_id');
    }

    /**
     * Get the database restores for this backup.
     */
    public function restores()
    {
        return $this->hasMany(DatabaseRestore::class, 'database_backup_id');
    }

    /**
     * Generate a new backup path for the given project.
     *
     * @param  \App\Project  $project
     * @return string
     */
    public static function newPathFor(Project $project)
    {
        return sprintf(
            'backups/%s/%s.sql.gz',
            Str::lower(Str::snake($project->name)),
            Carbon::now()->format('Y-m-d-H-i-s')
        );
    }

    /**
     * Get the configuration script for the storage provider.
     *
     * @return string
     */
    public function configurationScript()
    {
        return $this->storageProvider->client()->configurationScript();
    }

    /**
     * Get the upload script for the storage provider.
     *
     * @return string
     */
    public function uploadScript()
    {
        return $this->storageProvider->client()->uploadScript($this);
    }

    /**
     * Get the download script for the storage provider.
     *
     * @return string
     */
    public function downloadScript()
    {
        return $this->storageProvider->client()->downloadScript($this);
    }

    /**
     * Mark the database backup as running.
     *
     * @return void
     */
    public function markAsRunning()
    {
        DatabaseBackupRunning::dispatch(tap($this)->update([
            'status' => 'running',
        ]));
    }

    /**
     * Update the size of the backup.
     *
     * @return void
     */
    public function updateSize()
    {
        $this->update([
            'size' => $this->storageProvider->client()->size($this->backup_path)
        ]);
    }

    /**
     * Mark the database backup as finished.
     *
     * @param  string  $output
     * @return void
     */
    public function markAsFinished($output = '')
    {
        DatabaseBackupFinished::dispatch(tap($this)->update([
            'status' => 'finished',
            'exit_code' => 0,
            'output' => $output,
        ]));
    }

    /**
     * Restore the database from the backup.
     *
     * @return \App\DatabaseRestore
     */
    public function restore()
    {
        return tap($this->restores()->create([
            'database_id' => $this->database->id,
            'database_name' => $this->database_name,
            'status' => 'pending',
            'output' => '',
        ]), function ($restore) {
            $this->trimRestores();

            RestoreDatabaseBackup::dispatch($restore);
        });
    }

    /**
     * Trim the database restores for the backup.
     *
     * @return void
     */
    protected function trimRestores()
    {
        $restores = $this->restores()->get();

        if (count($restores) > 20) {
            $restores->slice(20 - count($restores))->each->delete();
        }
    }

    /**
     * Mark the database backup as failed.
     *
     * @param  int  $exitCode
     * @param  string  $output
     * @return void
     */
    public function markAsFailed($exitCode, $output = '')
    {
        DatabaseBackupFailed::dispatch(tap($this)->update([
            'status' => 'failed',
            'exit_code' => $exitCode,
            'output' => $output,
        ]));
    }

    /**
     * Delete the model from the database.
     *
     * @return bool|null
     *
     * @throws \Exception
     */
    public function delete()
    {
        DeleteDatabaseBackup::dispatch(
            $this->storageProvider, $this->backup_path
        );

        parent::delete();
    }
}
