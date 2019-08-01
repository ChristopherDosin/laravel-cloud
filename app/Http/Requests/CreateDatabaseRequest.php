<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateDatabaseRequest extends FormRequest
{
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
        return $this->validateRegionAndSize(validator($this->all(), [
            'name' => 'required|string|alpha_dash|max:255|unique:databases,name,NULL,id,project_id,'.$this->project->id,
            'size' => 'required|string',
        ]));
    }

    /**
     * Validate the size of the server.
     *
     * @param  \Illuminate\Validator\Validator  $validator
     * @return \Illuminate\Validator\Validator
     */
    protected function validateRegionAndSize($validator)
    {
        return $validator->after(function ($validator) {
            if (! $this->project->serverProvider->validSize($this->size)) {
                $validator->errors()->add('size', 'The provided size is invalid.');
            }
        });
    }
}
