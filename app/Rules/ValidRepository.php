<?php

namespace App\Rules;

use App\SourceProvider;
use Illuminate\Contracts\Validation\Rule;

class ValidRepository implements Rule
{
    /**
     * The source control provider instance.
     *
     * @var \App\SourceProvider
     */
    public $source;

    /**
     * The branch name.
     *
     * @var string|null
     */
    public $branch;

    /**
     * Create a new rule instance.
     *
     * @param  \App\SourceProvider  $source
     * @param  string|null  $branch
     * @return void
     */
    public function __construct($source, $branch = null)
    {
        $this->source = $source;
        $this->branch = $branch;
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
        if (! $this->source instanceof SourceProvider) {
            return false;
        }

        return $this->source->client()->validRepository(
            $value, $this->branch
        );
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The given repository or branch is invalid.';
    }
}
