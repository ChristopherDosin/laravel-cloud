<?php

namespace App\Http\Requests;

use App\MemoizesMethods;
use App\Rules\ValidBranch;
use Illuminate\Foundation\Http\FormRequest;

class CreateHookRequest extends FormRequest
{
    use MemoizesMethods;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validator for the request.
     *
     * @return \Illuminate\Validation\Validator
     */
    public function validator()
    {
        return validator($this->all(), [
            'name' => 'required|string|max:255',
            'branch' => [
                'required',
                'string',
                'max:255',
                new ValidBranch(
                    $this->stack->project()->sourceProvider, $this->stack->project()->repository
                )
            ],
            'publish' => 'required|boolean',
        ]);
    }
}
