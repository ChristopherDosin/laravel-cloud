<?php

namespace App\Rules;

use App\Project;
use Illuminate\Contracts\Validation\Rule;

class ValidSourceName implements Rule
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

        return ! is_null(
            $this->project->user->sourceProviders->where('name', $value)->first()
        );
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The given source control provider name is invalid.';
    }
}
