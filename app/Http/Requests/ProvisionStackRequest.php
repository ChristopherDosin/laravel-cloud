<?php

namespace App\Http\Requests;

use App\MemoizesMethods;
use App\Rules\ValidSize;
use App\Rules\ValidServeList;
use App\Rules\ValidDatabaseName;
use App\Contracts\StackDefinition;
use App\Rules\ValidAppServerStack;
use App\FiltersConfigurationArrays;
use App\Rules\DatabaseIsProvisioned;
use Illuminate\Foundation\Http\FormRequest;

class ProvisionStackRequest extends FormRequest implements StackDefinition
{
    use FiltersConfigurationArrays, MemoizesMethods;

    /**
     * Get the user that is creating the stack.
     *
     * @return \App\User
     */
    public function creator()
    {
        return $this->user();
    }

    /**
     * Get the project associated with the request.
     *
     * @return \App\Project
     */
    public function project()
    {
        return $this->environment->project;
    }

    /**
     * Get the environment for the request.
     *
     * @return  \App\Environment
     */
    public function environment()
    {
        return $this->environment;
    }

    /**
     * Extract the daemons from the request.
     *
     * @return array
     */
    public function daemons()
    {
        $daemons = [];

        if ($this->app && is_array($this->app)) {
            $daemons = $this->app['daemons'] ?? [];
        } elseif ($this->worker && is_array($this->worker)) {
            $daemons = $this->worker['daemons'] ?? [];
        }

        return empty($daemons) ? [] : $this->filterDaemons($daemons);
    }

    /**
     * Extract the scheduled tasks from the request.
     *
     * @return array
     */
    public function schedule()
    {
        $schedule = [];

        if ($this->app && is_array($this->app)) {
            $schedule = $this->app['schedule'] ?? [];
        } elseif ($this->worker && is_array($this->worker)) {
            $schedule = $this->worker['schedule'] ?? [];
        }

        return empty($schedule) ? [] : $this->filterSchedule($schedule);
    }

    /**
     * Extract the scripts from the request.
     *
     * @return array
     */
    public function scripts()
    {
        return [
            'app' => $this['app']['scripts'] ?? [],
            'web' => $this['web']['scripts'] ?? [],
            'worker' => $this['worker']['scripts'] ?? [],
        ];
    }

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
        return validator($this->all(), array_merge([
                'name' => 'required|string|max:255|alpha_dash',
            ],
            $this->databaseRules(),
            $this->sourceControlRules(),
            $this->appServerRules(),
            $this->webServerRules(),
            $this->workerServerRules(),
            $this->deploymentRules(),
            $this->metaRules()
        ))->after(function ($validator) {
            if (! $this->hasAppServers() && ! $this->hasWebServers()) {
                $validator->errors()->add('web', 'At least one web server must be defined.');
            }
        });
    }

    /**
     * Determine if the request has app servers.
     *
     * @return bool
     */
    protected function hasAppServers()
    {
        return is_array($this->app) and count($this->app) > 0;
    }

    /**
     * Determine if the request has web servers.
     *
     * @return bool
     */
    protected function hasWebServers()
    {
        return is_array($this->web) and count($this->web) > 0;
    }

    /**
     * Get the application server validation rules.
     *
     * @return array
     */
    protected function databaseRules()
    {
        return [
            'databases' => 'array|max:20',
            'databases.*' => [
                'string',
                new ValidDatabaseName($this->project()),
                new DatabaseIsProvisioned($this->project()),
            ],
        ];
    }

    /**
     * Get the source control validation rules.
     *
     * @return array
     */
    protected function sourceControlRules()
    {
        return [
            'branch' => 'required|string',
        ];
    }

    /**
     * Get the application server validation rules.
     *
     * @return array
     */
    protected function appServerRules()
    {
        return [
            'app' => ['array', new ValidAppServerStack($this)],
            'app.scale' => 'integer|between:1,1',
            'app.size' => ['required_with:app', 'string', new ValidSize($this->project())],
            'app.tls' => 'string|in:self-signed',
            'app.serves' => [
                'array',
                'min:1',
                new ValidServeList($this->project(), $this->environment()->name)
            ],
            'app.serves.*' => 'string',
            'app.daemons' => 'array',
            'app.daemons.*.command' => 'required_with:app.daemons|string',
            'app.daemons.*.directory' => 'string',
            'app.daemons.*.processes' => 'integer|min:1',
            'app.daemons.*.wait' => 'integer|min:1',
            'app.schedule.*' => 'array',
            'app.schedule.*.command' => 'required_with:app.schedule|string|max:1000',
            'app.schedule.*.frequency' => 'required_with:app.schedule|string|max:50',
            'app.schedule.*.user' => 'string|max:50',
            'app.scripts' => 'array',
            'app.scripts.*' => 'string',
        ];
    }

    /**
     * Get the web server validation rules.
     *
     * @return array
     */
    protected function webServerRules()
    {
        return [
            'web' => 'array',
            'web.scale' => 'integer',
            'web.size' => ['required_with:web', 'string', new ValidSize($this->project())],
            'web.tls' => 'string|in:self-signed',
            'web.serves' => [
                'array',
                'min:1',
                new ValidServeList($this->project(), $this->environment()->name)
            ],
            'web.serves.*' => 'string',
            'web.scripts' => 'array',
            'web.scripts.*' => 'string',
        ];
    }

    /**
     * Get the web server validation rules.
     *
     * @return array
     */
    protected function workerServerRules()
    {
        return [
            'worker' => 'array',
            'worker.scale' => 'integer',
            'worker.size' => ['required_with:worker', 'string', new ValidSize($this->project())],
            'worker.daemons' => 'array',
            'worker.daemons.*.command' => 'required_with:worker.daemons|string',
            'worker.daemons.*.directory' => 'string',
            'worker.daemons.*.processes' => 'integer|min:1',
            'worker.daemons.*.wait' => 'integer|min:1',
            'worker.schedule.*' => 'array',
            'worker.schedule.*.command' => 'required_with:worker.schedule|string|max:1000',
            'worker.schedule.*.frequency' => 'required_with:worker.schedule|string|max:50',
            'worker.schedule.*.user' => 'string|max:50',
            'worker.scripts' => 'array',
            'worker.scripts.*' => 'string',
        ];
    }

    /**
     * Get the deployment validation rules.
     *
     * @return array
     */
    protected function deploymentRules()
    {
        return [
            'build' => 'array',
            'build.*' => 'string',
            'activate' => 'array',
            'activate.*' => 'string',
            'directories' => 'array',
            'directories.*' => 'string',
        ];
    }

    /**
     * Get the meta validation rules.
     *
     * @return array
     */
    protected function metaRules()
    {
        return [
            'meta' => 'array',
        ];
    }
}
