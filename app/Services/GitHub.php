<?php

namespace App\Services;

use App\Hook;
use App\Stack;
use Exception;
use App\Deployment;
use App\Environment;
use GuzzleHttp\Client;
use App\SourceProvider;
use App\Contracts\SourceProviderClient;
use GuzzleHttp\Exception\ClientException;
use App\Exceptions\ManifestNotFoundException;

class GitHub implements SourceProviderClient
{
    /**
     * The source instance.
     *
     * @var Source
     */
    protected $source;

    /**
     * Create a new GitHub service instance.
     *
     * @param  Source  $source
     * @return void
     */
    public function __construct(SourceProvider $source)
    {
        $this->source = $source;
    }

    /**
     * Determine if the source control credentials are valid.
     *
     * @return bool
     */
    public function valid()
    {
        try {
            $response = $this->request('get', '/user/repos');

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Validate the given repository and branch are valid.
     *
     * @param  string  $repository
     * @param  string  $branch
     * @return bool
     */
    public function validRepository($repository, $branch)
    {
        if (empty($repository)) {
            return false;
        }

        try {
            $response = $this->request('get', "/repos/{$repository}/branches");
        } catch (ClientException $e) {
            return false;
        }

        if (empty($branch)) {
            return true;
        }

        return collect($response)->contains(function ($b) use ($branch) {
            return $b['name'] === $branch;
        });
    }

    /**
     * Validate the given repository and commit hash are valid.
     *
     * @param  string  $repository
     * @param  string  $hash
     * @return bool
     */
    public function validCommit($repository, $hash)
    {
        if (empty($repository) || empty($hash)) {
            return false;
        }

        try {
            $response = $this->request('get', "/repos/{$repository}/commits/{$hash}");
        } catch (ClientException $e) {
            return false;
        }

        return $response['sha'] === $hash;
    }

    /**
     * Get the latest commit hash for the given repository and branch.
     *
     * @param  string  $repository
     * @param  string  $branch
     * @return string
     */
    public function latestHashFor($repository, $branch)
    {
        return $this->request(
            'get', "/repos/{$repository}/commits?sha={$branch}&per_page=1"
        )[0]['sha'];
    }

    /**
     * Get the tarball URL for the given deployment.
     *
     * @param  \App\Deployment  $deployment
     * @return string
     */
    public function tarballUrl(Deployment $deployment)
    {
        return sprintf(
            'https://api.github.com/repos/%s/tarball/%s?access_token=%s',
            $deployment->repository(),
            $deployment->commit_hash,
            $this->token()
        );
    }

    /**
     * Publish the given hook.
     *
     * @param  \App\Hook  $hook
     * @return void
     */
    public function publishHook(Hook $hook)
    {
        $this->deleteHooksWithMatchingUrl($hook);

        $response = $this->request('post', '/repos/'.$hook->project()->repository.'/hooks', [
            'name' => 'web',
            'config' => [
                'url' => $hook->url(),
                'content_type' => 'json'
            ],
            'events' => ['push'],
            'active' => true,
        ]);

        $hook->update([
            'published' => true,
            'meta' => array_merge($hook->meta, [
                'provider_hook_id' => $response['id'],
            ])
        ]);
    }

    /**
     * Determine if the given hook payload is a test.
     *
     * @param  \App\Hook  $hook
     * @param  array  $payload
     * @return bool
     */
    public function isTestHookPayload(Hook $hook, array $payload)
    {
        return isset($payload['zen']);
    }

    /**
     * Determine if the given hook payload applies to the hook.
     *
     * @param  \App\Hook  $hook
     * @param  array  $payload
     * @return bool
     */
    public function receivesHookPayload(Hook $hook, array $payload)
    {
        return ! $this->isTestHookPayload($hook, $payload) &&
               $payload['ref'] == "refs/heads/{$hook->branch}" &&
               $payload['repository']['full_name'] == $hook->project()->repository;
    }

    /**
     * Get the commit hash from the given hook payload.
     *
     * @param  array  $payload
     * @return string|null
     */
    public function extractCommitFromHookPayload(array $payload)
    {
        return $payload['head_commit']['id'] ?? null;
    }

    /**
     * Unpublish the given hook.
     *
     * @param  \App\Hook  $hook
     * @return void
     */
    public function unpublishHook(Hook $hook)
    {
        if (! $providerHookId = ($hook->meta['provider_hook_id'] ?? null)) {
            return;
        }

        $this->deleteHookById($hook->project()->repository, $providerHookId);

        $hook->update([
            'published' => false,
            'meta' => array_filter(array_merge($hook->meta, [
                'provider_hook_id' => null,
            ]))
        ]);
    }

    /**
     * Delete any hooks matching the given hooks URL.
     *
     * @param  \App\Hook  $hook
     * @return void
     */
    protected function deleteHooksWithMatchingUrl(Hook $hook)
    {
        if ($existingHook = $this->findHookWithMatchingUrl($hook)) {
            $this->deleteHookById($hook->project()->repository, $existingHook['id']);
        }
    }

    /**
     * Find a hook by the given hook's URL.
     *
     * @param  \App\Hook  $hook
     * @return array|null
     */
    protected function findHookWithMatchingUrl(Hook $hook)
    {
        $url = $hook->url();

        return collect($this->request('get', '/repos/'.$hook->project()->repository.'/hooks'))
            ->first(function ($hook) use ($url) {
                return ($hook['config']['url'] ?? null) == $url;
            });
    }

    /**
     * Delete a hook by the given repository and ID.
     *
     * @param  string  $repository
     * @param  string  $id
     * @return void
     */
    protected function deleteHookById($repository, $id)
    {
        $this->request('delete', '/repos/'.$repository.'/hooks/'.$id);
    }

    /**
     * Get the manifest content for the given stack and hash.
     *
     * @param  \App\Stack  $stack
     * @param  string  $repository
     * @param  string  $hash
     * @return string
     */
    public function manifest(Stack $stack, $repository, $hash)
    {
        try {
            $response = $this->request(
                'get', '/repos/'.$repository.'/contents/.cloud/'.$stack->environment->name.'/'.$stack->name.'.yml?ref='.$hash
            );

            return base64_decode($response['content']);
        } catch (Exception $e) {
            report($e);

            throw new ManifestNotFoundException($stack, $repository, $hash);
        }
    }

    /**
     * Make an HTTP request to GitHub.
     *
     * @param  string  $method
     * @param  string  $path
     * @param  array  $parameters
     * @return array
     */
    protected function request($method, $path, array $parameters = [])
    {
        $response = (new Client)->{$method}('https://api.github.com/'.ltrim($path, '/'), [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'Authorization' => 'token '.$this->token(),
            ],
            'json' => $parameters,
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Get the authentication token for the provider.
     *
     * @return string
     */
    protected function token()
    {
        return $this->source->meta['token'];
    }
}
