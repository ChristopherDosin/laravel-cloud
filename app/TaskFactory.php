<?php

namespace App;

class TaskFactory
{
    /**
     * Create a new task instance.
     *
     * @param  \App\Contracts\Provisionable  $provisionable
     * @param  \App\Scripts\Script  $script
     * @param  array $options
     * @return \App\Task
     */
    public function createFromScript($provisionable, $script, array $options = [])
    {
        if (! array_key_exists('timeout', $options)) {
            $options['timeout'] = $script->timeout();
        }

        return $provisionable->tasks()->create([
            'project_id' => $provisionable->projectId(),
            'name' => $script->name(),
            'user' => $script->sshAs,
            'options' => $options,
            'script' => (string) $script,
            'output' => '',
        ]);
    }
}
