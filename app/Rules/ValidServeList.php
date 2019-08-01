<?php

namespace App\Rules;

use App\Project;
use Illuminate\Contracts\Validation\Rule;

class ValidServeList implements Rule
{
    /**
     * The project instance.
     *
     * @var \App\Project
     */
    public $project;

    /**
     * The environment that should be excluded.
     *
     * @var string
     */
    public $except;

    /**
     * Create a new rule instance.
     *
     * @param  \App\Project  $project
     * @param  string  $exceptEnvironment
     * @return void
     */
    public function __construct($project, $exceptEnvironment = null)
    {
        $this->project = $project;
        $this->except = $exceptEnvironment;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (! $this->project instanceof Project || empty($value)) {
            return true;
        }

        foreach ($this->project->environments as $environment) {
            if ($environment->name == $this->except) {
                continue;
            }

            foreach ($environment->stacks as $stack) {
                if (array_intersect($value, $stack->serves())) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'These domains are already being served by another environment.';
    }
}
