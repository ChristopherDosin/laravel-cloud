<?php

namespace App;

use Facades\App\Contracts\YamlParser;
use App\Http\Requests\CreateDeploymentRequest;

class DeploymentInstructions
{
    use FiltersConfigurationArrays;

    /**
     * The repository name.
     *
     * @var string
     */
    public $repository;

    /**
     * The commit hash.
     *
     * @var string
     */
    public $hash;

    /**
     * The build commands.
     *
     * @var array
     */
    public $build;

    /**
     * The activation commands.
     *
     * @var array
     */
    public $activate;

    /**
     * The persistent directories.
     *
     * @var array
     */
    public $directories;

    /**
     * The deployment daemons.
     *
     * @var array
     */
    public $daemons;

    /**
     * Create a new deployment instructions instance.
     *
     * @param  string  $hash
     * @param  array  $build
     * @param  array  $activate
     * @param  array  $directories
     * @param  array  $daemons
     * @param  array  $schedule
     * @return void
     */
    public function __construct($hash, array $build, array $activate,
                                array $directories, array $daemons,
                                array $schedule)
    {
        $this->hash = $hash;
        $this->build = $build;
        $this->activate = $activate;
        $this->directories = $directories;

        $this->daemons = $this->filterDaemons($daemons);
        $this->schedule = $this->filterSchedule($schedule);
    }

    /**
     * Create new deployment instructions from a request.
     *
     * @param  \App\Http\Requests\CreateDeploymentRequest  $request
     * @return static
     */
    public static function fromRequest(CreateDeploymentRequest $request)
    {
        $project = $request->stack->project();

        $hash = $request->hash ?: $project->sourceProvider->client()->latestHashFor(
            $project->repository, $request->branch
        );

        return new static(
            $hash, $request->build ?? [], $request->activate ?? [],
            $request->directories ?? [], $request->daemons() ?? [],
            $request->schedule() ?? []
        );
    }

    /**
     * Create new deployment instructions from the given hook and hash.
     *
     * @param  \App\Hook  $hook
     * @param  string|null  $hash
     * @return static
     */
    public static function fromHookCommit(Hook $hook, $hash)
    {
        $client = $hook->sourceProvider()->client();

        $manifest = YamlParser::parse($client->manifest(
            $hook->stack, $hook->repository(), $hash
        ));

        return new static(
            $hash, $manifest['build'] ?? [], $manifest['activate'] ?? [],
            $manifest['directories'] ?? [], static::daemons($manifest),
            static::schedule($manifest)
        );
    }

    /**
     * Create new deployment instructions from the given hook.
     *
     * @param  \App\Hook  $hook
     * @return static
     */
    public static function forLatestHookCommit(Hook $hook)
    {
        $client = $hook->sourceProvider()->client();

        return static::fromHookCommit(
            $hook, $client->latestHashFor($hook->repository(), $hook->branch)
        );
    }

    /**
     * Extract the daemons from the given manifest array.
     *
     * @param  array  $manifest
     * @return array
     */
    public static function daemons(array $manifest)
    {
        if (isset($manifest['app'])) {
            return $manifest['app']['daemons'] ?? [];
        } elseif (isset($manifest['worker'])) {
            return $manifest['worker']['daemons'] ?? [];
        }

        return [];
    }

    /**
     * Extract the scheduled tasks from the given manifest array.
     *
     * @param  array  $manifest
     * @return array
     */
    public static function schedule(array $manifest)
    {
        if (isset($manifest['app'])) {
            return $manifest['app']['schedule'] ?? [];
        } elseif (isset($manifest['worker'])) {
            return $manifest['worker']['schedule'] ?? [];
        }

        return [];
    }
}
