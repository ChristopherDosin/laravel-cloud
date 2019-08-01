<?php

namespace App;

use App\Events\DatabaseRestoreFailed;
use App\Events\DatabaseRestoreRunning;
use App\Events\DatabaseRestoreFinished;
use Illuminate\Database\Eloquent\Model;

class DatabaseRestore extends Model
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
     * The database that the restore belongs to.
     */
    public function database()
    {
        return $this->belongsTo(Database::class, 'database_id');
    }

    /**
     * Get the database backup the restore belongs to.
     */
    public function backup()
    {
        return $this->belongsTo(DatabaseBackup::class, 'database_backup_id');
    }

    /**
     * Mark the database restore as running.
     *
     * @return void
     */
    public function markAsRunning()
    {
        DatabaseRestoreRunning::dispatch(tap($this)->update([
            'status' => 'running',
        ]));
    }

    /**
     * Mark the database restore as finished.
     *
     * @param  string  $output
     * @return void
     */
    public function markAsFinished($output = '')
    {
        DatabaseRestoreFinished::dispatch(tap($this)->update([
            'status' => 'finished',
            'exit_code' => 0,
            'output' => $output,
        ]));
    }

    /**
     * Mark the database restore as failed.
     *
     * @param  int  $exitCode
     * @param  string  $output
     * @return void
     */
    public function markAsFailed($exitCode, $output = '')
    {
        DatabaseRestoreFailed::dispatch(tap($this)->update([
            'status' => 'failed',
            'exit_code' => $exitCode,
            'output' => $output,
        ]));
    }
}
