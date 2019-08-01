<?php

namespace App\Http\Requests;

use App\MemoizesMethods;
use App\DeploymentInstructions;
use Illuminate\Foundation\Http\FormRequest;

class CreateHookDeploymentRequest extends FormRequest
{
    use MemoizesMethods;

    /**
     * Determine if this hook deployment request is receivable.
     *
     * @return bool
     */
    public function receivable()
    {
        if ($this->fromCodeship()) {
            return $this->input('build')['status'] == 'success';
        }

        return true;
    }

    /**
     * Get the commit hash being deployed.
     *
     * @return string
     */
    public function hash()
    {
        return once(function () {
            switch (true) {
                case $this->fromCodeship():
                    return $this->hashFromCodeship();

                default:
                    return $this->hashFromSourceProvider();
            }
        });
    }

    /**
     * Geth the commit hash from the Codeship payload.
     *
     * @return string
     */
    protected function hashFromCodeship()
    {
        return $this->input('build')['commit_id'] ?? null;
    }

    /**
     * Geth the commit hash from the source control provider.
     *
     * @return string
     */
    protected function hashFromSourceProvider()
    {
        return $this->sourceProviderClient()->extractCommitFromHookPayload($this->all());
    }

    /**
     * Get the deployment instructions for the commit.
     *
     * @return \App\DeploymentInstructions
     */
    public function instructions()
    {
        return once(function () {
            return $this->hash()
                    ? DeploymentInstructions::fromHookCommit($this->hook, $this->hash())
                    : DeploymentInstructions::forLatestHookCommit($this->hook);
        });
    }

    /**
     * Determine if the request is from Codeship.
     *
     * @return bool
     */
    protected function fromCodeship()
    {
        return $this->userAgent() == 'Codeship Webhook';
    }

    /**
     * Get the source control provider client instance.
     *
     * @return \App\Contracts\SourceProviderClient
     */
    protected function sourceProviderClient()
    {
        return $this->hook->sourceProvider()->client();
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
        return validator($this->all(), []);
    }
}
