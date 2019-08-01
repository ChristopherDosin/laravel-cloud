<?php

namespace App\Http\Requests;

use App\SourceProvider;
use App\Rules\ValidRepository;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CreateProjectRequest extends FormRequest
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
        $validator = validator($this->all(), [
            'name' => 'required|max:255',
            'server_provider_id' => ['required', Rule::exists('server_providers', 'id')->where(function ($query) {
                $query->where('user_id', $this->user()->id);
            })],
            'region' => 'required|string',
            'source_provider_id' => ['required', Rule::exists('source_providers', 'id')->where(function ($query) {
                $query->where('user_id', $this->user()->id);
            })],
            'repository' => [
                'required',
                'string',
                new ValidRepository(SourceProvider::find($this->source_provider_id))
            ],
            'database' => 'string|alpha_dash|max:255',
            'database_size' => 'string',
        ]);

        return $this->validateRegionAndSize($validator);
    }

    /**
     * Validate the region and size for the provider.
     *
     * @param  \Illuminate\Validator\Validator  $validator
     * @return \Illuminate\Validator\Validator
     */
    protected function validateRegionAndSize($validator)
    {
        return $validator->after(function ($validator) {
            $provider = $this->user()->serverProviders()->find($this->server_provider_id);

            if (! $provider->validRegion($this->region)) {
                $validator->errors()->add('region', 'Invalid region for provider.');
            }

            if ($this->database && ! $provider->validSize($this->database_size)) {
                $validator->errors()->add('database_size', 'Invalid size for database.');
            }
        });
    }
}
