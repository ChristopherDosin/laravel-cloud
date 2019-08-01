<?php

namespace App;

use App\Scripts\RunServerTask;
use App\Events\ServerTaskFailed;
use App\Callbacks\CheckServerTask;
use App\Events\ServerTaskFinished;
use Illuminate\Database\Eloquent\Model;

class ServerTask extends Model
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'commands' => 'json',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the stack task that the server task belongs to.
     */
    public function stackTask()
    {
        return $this->belongsTo(StackTask::class, 'stack_task_id');
    }

    /**
     * Get the entity the task belongs to.
     */
    public function taskable()
    {
        return $this->morphTo();
    }

    /**
     * Get the underlying task for the server task.
     */
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * Determine if the server task is pending.
     *
     * @return bool
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Determine if the server task is running.
     *
     * @return bool
     */
    public function isRunning()
    {
        return $this->status === 'running';
    }

    /**
     * Run the server task.
     *
     * @return void
     */
    public function run()
    {
        $task = $this->taskable->runInBackground(new RunServerTask($this), [
            'then' => [new CheckServerTask($this->id)],
        ]);

        $this->update([
            'status' => 'running',
            'task_id' => $task->id,
        ]);
    }

    /**
     * Determine if the server task has finished.
     *
     * @return bool
     */
    public function isFinished()
    {
        return $this->status === 'finished';
    }

    /**
     * Mark the server task as finished.
     *
     * @return void
     */
    public function markAsFinished()
    {
        $this->update(['status' => 'finished']);

        ServerTaskFinished::dispatch($this);

        $this->stackTask->syncStatus();
    }

    /**
     * Determine if the server task has failed.
     *
     * @return bool
     */
    public function hasFailed()
    {
        return $this->status === 'failed';
    }

    /**
     * Mark the server task as failed.
     *
     * @return void
     */
    public function markAsFailed()
    {
        $this->update(['status' => 'failed']);

        ServerTaskFailed::dispatch($this);

        $this->stackTask->syncStatus();
    }
}
