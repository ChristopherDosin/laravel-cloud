<?php

namespace App\Rules;

use App\Project;
use Illuminate\Contracts\Validation\Rule;

class ValidSize implements Rule
{
    /**
     * The project instance.
     *
     * @var \App\Project
     */
    public $project;

    /**
     * Create a new rule instance.
     *
     * @param  \App\Project  $project
     * @return void
     */
    public function __construct($project)
    {
        $this->project = $project;
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
        if (! $this->project instanceof Project) {
            return true;
        }

        return $this->project->serverProvider->validSize($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The specified server size is invalid.';
    }
}
