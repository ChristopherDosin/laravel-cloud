<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidAppServerStack implements Rule
{
    /**
     * The request instance.
     *
     * @var \App\Http\Requests\CreateStackRequest
     */
    public $request;

    /**
     * Create a new rule instance.
     *
     * @param  \App\Http\Requests\CreateStackRequest  $request
     * @return void
     */
    public function __construct($request)
    {
        $this->request = $request;
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
        return empty($value) || (empty($this->request->web) && empty($this->request->worker));
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'App servers may not be provisioned with web and worker servers.';
    }
}
