<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use InteractsWithSsh, Prunable;

    /**
     * The default timeout for tasks.
     *
     * @var int
     */
    const DEFAULT_TIMEOUT = 3600;

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
        'options',
        'output',
        'script',
    ];

    /**
     * Get the project that the task belongs to.
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the provisionable entity the task belongs to.
     */
    public function provisionable()
    {
        return $this->morphTo();
    }

    /**
     * Determine if the task was successful.
     *
     * @return bool
     */
    public function successful()
    {
        return (int) $this->exit_code === 0;
    }

    /**
     * Get the maximum execution time for the task.
     *
     * @return int
     */
    public function timeout()
    {
        return (int) ($this->options['timeout'] ?? Task::DEFAULT_TIMEOUT);
    }

    /**
     * Get the value of the options array.
     *
     * @param  string  $value
     * @return array
     */
    public function getOptionsAttribute($value)
    {
        return unserialize($value);
    }

    /**
     * Set the value of the options array.
     *
     * @param  array  $value
     * @return array
     */
    public function setOptionsAttribute(array $value)
    {
        $this->attributes['options'] = serialize($value);
    }

    /**
     * Mark the task as finished and gather its output.
     *
     * @param  int  $exitCode
     * @return void
     */
    public function finish($exitCode = 0)
    {
        $this->markAsFinished($exitCode);

        $this->update([
            'output' => $this->retrieveOutput(),
        ]);

        foreach ($this->options['then'] ?? [] as $callback) {
            is_object($callback)
                        ? $callback->handle($this)
                        : app($callback)->handle($this);
        }
    }

    /**
     * Mark the task as running.
     *
     * @return $this
     */
    protected function markAsRunning()
    {
        return tap($this)->update([
            'status' => 'running',
        ]);
    }

    /**
     * Determine if the task is running.
     *
     * @return bool
     */
    public function isRunning()
    {
        return $this->status === 'running';
    }

    /**
     * Mark the task as timed out.
     *
     * @param  string  $output
     * @return $this
     */
    protected function markAsTimedOut($output = '')
    {
        return tap($this)->update([
            'exit_code' => 1,
            'status' => 'timeout',
            'output' => $output,
        ]);
    }

    /**
     * Mark the task as finished.
     *
     * @param  int  $exitCode
     * @param  string  $output
     * @return $this
     */
    protected function markAsFinished($exitCode = 0, $output = '')
    {
        return tap($this)->update([
            'exit_code' => $exitCode,
            'status' => 'finished',
            'output' => $output,
        ]);
    }
}
