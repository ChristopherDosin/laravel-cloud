<?php

namespace App;

use App\Jobs\RunStackTask;
use App\Events\StackTaskFailed;
use App\Events\StackTaskRunning;
use App\Events\StackTaskFinished;
use Illuminate\Database\Eloquent\Model;

class StackTask extends Model
{
    use Prunable;

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
     * Get the stack that the task belongs to.
     */
    public function stack()
    {
        return $this->belongsTo(Stack::class, 'stack_id');
    }

    /**
     * Get all of the server tasks for the stack task.
     */
    public function serverTasks()
    {
        return $this->hasMany(ServerTask::class, 'stack_task_id');
    }

    /**
     * Queue the stack task to execution.
     *
     * @return void
     */
    public function dispatch()
    {
        RunStackTask::dispatch($this);
    }

    /**
     * Run the task.
     *
     * @return void
     */
    public function run()
    {
        $this->update([
            'status' => 'running',
        ]);

        $this->stack->allServers()->each(function ($server) {
            $commands = $this->shellCommands()->filter->appliesTo($server)->reject->prefixed(
                ! $server->isMaster() ? 'once:' : null
            )->map->trim()->values()->all();

            if (empty($commands)) {
                return;
            }

            $this->serverTasks()->create([
                'taskable_id' => $server->id,
                'taskable_type' => get_class($server),
                'commands' => $commands,
            ])->run();
        });

        if ($this->serverTasks()->count() === 0) {
            return $this->markAsFinished();
        }

        StackTaskRunning::dispatch($this);
    }

    /**
     * Get the task commands mapped into ShellCommand instances.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function shellCommands()
    {
        return collect($this->commands)->mapInto(ShellCommand::class);
    }

    /**
     * Sync the stack task status based on the server tasks.
     *
     * @return void
     */
    public function syncStatus()
    {
        if ($this->serverTasks->contains->isRunning() ||
            $this->serverTasks->contains->isPending()) {
            return;
        }

        if ($this->serverTasks->contains->hasFailed()) {
            $this->markAsFailed();
        } else {
            $this->markAsFinished();
        }
    }

    /**
     * Determine if the stack task has finished.
     *
     * @return bool
     */
    public function isFinished()
    {
        return $this->status === 'finished';
    }

    /**
     * Mark the stack task as finished.
     *
     * @return void
     */
    public function markAsFinished()
    {
        $this->update(['status' => 'finished']);

        StackTaskFinished::dispatch($this);
    }

    /**
     * Determine if th stack task has failed.
     *
     * @return bool
     */
    public function hasFailed()
    {
        return $this->status === 'failed';
    }

    /**
     * Mark the stack task as failed.
     *
     * @return void
     */
    public function markAsFailed()
    {
        $this->update(['status' => 'failed']);

        StackTaskFailed::dispatch($this);
    }
}
