<?php

namespace App\Http\Requests;

use App\MemoizesMethods;
use App\Rules\ValidBranch;
use App\Rules\ValidCommit;
use App\FiltersConfigurationArrays;
use Illuminate\Foundation\Http\FormRequest;

class CreateDeploymentRequest extends FormRequest
{
    use FiltersConfigurationArrays, MemoizesMethods;

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
            'branch' => ['required_without:hash', 'string', 'max:255', new ValidBranch(
                $this->stack->project()->sourceProvider, $this->stack->project()->repository
            )],
            'hash' => ['required_without:branch', 'string', 'max:40', new ValidCommit(
                $this->stack->project()->sourceProvider, $this->stack->project()->repository
            )],
            'build' => 'array',
            'build.*' => 'string',
            'activate' => 'array',
            'activate.*' => 'string',
            'daemons' => 'array',
            'daemons.*.command' => 'required_with:daemons|string',
            'daemons.*.directory' => 'string',
            'daemons.*.processes' => 'integer|min:1',
            'daemons.*.wait' => 'integer|min:1',
            'schedule.*' => 'array',
            'schedule.*.command' => 'required_with:schedule|string|max:1000',
            'schedule.*.frequency' => 'required_with:schedule|string|max:50',
            'schedule.*.user' => 'string|max:50',
        ]);
    }

    /**
     * Extract the daemons from the request.
     *
     * @return array
     */
    public function daemons()
    {
        return empty($this->daemons) ? [] : $this->filterDaemons($this->daemons);
    }

    /**
     * Extract the scheduled tasks from the request.
     *
     * @return array
     */
    public function schedule()
    {
        return empty($this->schedule) ? [] : $this->filterSchedule($this->schedule);
    }
}
