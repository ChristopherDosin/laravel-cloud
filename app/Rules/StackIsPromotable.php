<?php

namespace App\Rules;

use App\Stack;
use Illuminate\Contracts\Validation\Rule;

class StackIsPromotable implements Rule
{
    /**
     * The stack instance.
     *
     * @var \App\Stack
     */
    public $stack;

    /**
     * Create a new rule instance.
     *
     * @param  \App\Stack  $stack
     * @return void
     */
    public function __construct($stack)
    {
        $this->stack = $stack;
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
        return $this->stack->promotable();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The specified stack is not promotable. Please verify the stack has a "serves" directive.';
    }
}
