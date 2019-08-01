<?php

namespace App;

use Exception;
use Carbon\Carbon;
use App\Jobs\SyncServers;
use Illuminate\Support\Str;
use App\Events\StackDeleting;
use App\Events\StackProvisioned;
use App\Events\StackProvisioning;
use App\Contracts\StackDefinition;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use App\Exceptions\AlreadyDeployingException;

class Stack extends Model
{
    use DeterminesAge;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'balanced' => 'boolean',
        'meta' => 'json',
        'pending_deployment' => 'json',
        'promoted' => 'boolean',
        'under_maintenance' => 'boolean',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the project for the stack.
     *
     * @return \App\Project
     */
    public function project()
    {
        return $this->environment->project;
    }

    /**
     * Get the environment that the project belongs to.
     */
    public function environment()
    {
        return $this->belongsTo(Environment::class, 'environment_id');
    }

    /**
     * Get the creator of the stack.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the region the stack is located in.
     *
     * @return string
     */
    public function region()
    {
        return $this->environment->project->region;
    }

    /**
     * Get all of the hooks attached to the stack.
     */
    public function hooks()
    {
        return $this->hasMany(Hook::class);
    }

    /**
     * Get the databases attached to the stack.
     */
    public function databases()
    {
        return $this->belongsToMany(
            Database::class, 'stack_databases', 'stack_id', 'database_id'
        );
    }

    /**
     * Get all of the load balancers available to the stack.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function balancers()
    {
        return $this->environment->project->balancers;
    }

    /**
     * Get the largest available balancer.
     *
     * @return \App\Balancer|null
     */
    public function largestAvailableBalancer()
    {
        return $this->environment->project->largestAvailableBalancer();
    }

    /**
     * Get the recommended balancer size for the stack.
     *
     * @return string
     */
    public function recommendedBalancerSize()
    {
        return count($this->webServers) > 0
                            ? $this->webServers->first()->recommendedBalancerSize()
                            : $this->appServer->recommendedBalancerSize();
    }

    /**
     * Get the custom certificate associated with the stack.
     */
    public function certificate()
    {
        return $this->belongsTo(Certificate::class, 'certificate_id');
    }

    /**
     * Determine if this stack is promotable.
     *
     * @return bool
     */
    public function promotable()
    {
        return count($this->serves()) > 0;
    }

    /**
     * Get the domains served by the stack.
     *
     * @return array
     */
    public function serves()
    {
        return $this->appServer
                    ? ($this->appServer->meta['serves'] ?? [])
                    : ($this->webServers->first()->meta['serves'] ?? []);
    }

    /**
     * Get all of the servers in the stack.
     *
     * @return \Illuminate\Support\Collection
     */
    public function allServers()
    {
        return collect(array_merge(
            $this->appServers->sortBy('id')->all(),
            $this->webServers->sortBy('id')->all(),
            $this->workerServers->sortBy('id')->all()
        ));
    }

    /**
     * Get all of the application / web servers in the stack.
     *
     * @return \Illuminate\Support\Collection
     */
    public function httpServers()
    {
        return collect(array_merge(
            $this->appServers->sortBy('id')->all(),
            $this->webServers->sortBy('id')->all()
        ));
    }

    /**
     * Refresh the stack's server configurations.
     *
     * @return void
     */
    public function syncServers()
    {
        SyncServers::dispatch($this);
    }

    /**
     * Determine if the stack is completely provisioned.
     *
     * @return bool
     */
    public function serversAreProvisioned()
    {
        return $this->allServers()->where(
            'status', 'provisioned'
        )->count() === count($this->allServers());
    }

    /**
     * Get the master web server for the stack.
     *
     * @return \App\WebServer|\App\AppServer|null
     */
    public function masterServer()
    {
        return $this->appServer ?: $this->webServers->sortBy->id->first();
    }

    /**
     * Get the master worker server for the stack.
     *
     * @return \App\AppServer|\App\WorkerServer|null
     */
    public function masterWorker()
    {
        if ($this->appServer && count($this->workerServers) === 0) {
            return $this->appServer;
        }

        return count($this->workerServers) > 0
                ? $this->workerServers->sortBy->id->first() : null;
    }

    /**
     * Get the app server that belong to the stack.
     */
    public function appServer()
    {
        return $this->hasOne(AppServer::class);
    }

    /**
     * Get the app server that belong to the stack in an array.
     */
    public function appServers()
    {
        return $this->hasMany(AppServer::class);
    }

    /**
     * Get all of the web servers that belong to the stack.
     */
    public function webServers()
    {
        return $this->hasMany(WebServer::class);
    }

    /**
     * Get all of the worker servers that belong to the stack.
     */
    public function workerServers()
    {
        return $this->hasMany(WorkerServer::class);
    }

    /**
     * Get all of the stack tasks associated with the stack.
     */
    public function tasks()
    {
        return $this->hasMany(StackTask::class)->latest('id');
    }

    /**
     * Run a new task on the stack.
     *
     * @param  string  $name
     * @param  string  $user
     * @param  array  $commands
     * @return \App\StackTask
     */
    public function dispatchTask($name, $user, array $commands)
    {
        return tap($this->tasks()->create([
            'name' => $name,
            'user' => $user,
            'commands' => $commands,
        ]), function ($task) {
            $task->dispatch();

            $this->trimTasks();
        });
    }

    /**
     * Trim the stack's tasks.
     *
     * @return void
     */
    protected function trimTasks()
    {
        $tasks = $this->tasks()->get();

        if (count($tasks) > 20) {
            $tasks->slice(20 - count($tasks))->each->delete();
        }
    }

    /**
     * Get the entrypoint IP address for the stack.
     *
     * @return string
     */
    public function entrypoint()
    {
        if ($this->balanced) {
            return $this->largestAvailableBalancer()->address->public_address;
        }

        if ($this->masterServer() && $this->masterServer()->address) {
            return $this->masterServer()->address->public_address;
        }
    }

    /**
     * Get all of the IP addresses for the stack.
     *
     * @return array
     */
    public function allIpAddresses()
    {
        return $this->allServers()->flatMap(function ($server) {
            return array_filter([
                $server->address->public_address,
                $server->address->private_address,
            ]);
        });
    }

    /**
     * Get all of the private web addresses the stack exposes.
     *
     * @return array
     */
    public function privateWebAddresses()
    {
        return collect(array_merge(
            $this->appServers()->with('address')->get()->all(),
            $this->webServers()->with('address')->get()->all()
        ))->map(function ($server) {
            return 'https://'.$server->address->private_address;
        })->all();
    }

    /**
     * Get the array of domains the server should respond to.
     *
     * @return array
     */
    public function shouldRespondTo()
    {
        if (! $this->promoted) {
            return [$this->url.'.laravel.build'];
        }

        return array_unique(array_merge(
            [$this->url.'.laravel.build'],
            $this->masterServer()->shouldRespondTo()
        ));
    }

    /**
     * Get the array of domains / ports the server should respond to.
     *
     * @return array
     */
    public function shouldRespondToWithPorts()
    {
        return collect($this->shouldRespondTo())->flatMap(function ($domain) {
            return [$domain.':80', $domain.':443'];
        })->all();
    }

    /**
     * Get all of the stack's vanity domain's with ports.
     *
     * @return array
     */
    public function actualDomainsWithPorts()
    {
        return collect($this->shouldRespondToWithPorts())->reject(function ($domain) {
            return Str::contains($domain, 'laravel.build');
        })->values()->all();
    }

    /**
     * Get all of the stack's vanity domain's with ports.
     *
     * @return array
     */
    public function vanityDomainsWithPorts()
    {
        return collect($this->shouldRespondToWithPorts())->filter(function ($domain) {
            return Str::contains($domain, 'laravel.build');
        })->unique()->values()->all();
    }

    /**
     * Determine if the given domain is the canonical domain.
     *
     * @param  string  $domain
     * @return bool
     */
    public function isCanonicalDomain($domain)
    {
        $domain = str_replace(['http://', 'https://', ':80', ':443'], '', $domain);

        return $this->canonicalDomain($domain) === $domain;
    }

    /**
     * Determine the canonical domain for the given domain.
     *
     * @param  string  $domain
     * @return string
     */
    public function canonicalDomain($domain)
    {
        $domain = str_replace(['http://', 'https://', ':80', ':443'], '', $domain);

        if (in_array($domain, $canonicals = $this->masterServer()->meta['serves'] ?? [])) {
            return $domain;
        }

        if (Str::startsWith($domain, 'www.') &&
            in_array(Str::replaceFirst('www.', '', $domain), $canonicals)) {
            return Str::replaceFirst('www.', '', $domain);
        }

        if (! Str::startsWith($domain, 'www.') && in_array('www.'.$domain, $canonicals)) {
            return 'www.'.$domain;
        }

        return $domain;
    }

    /**
     * Get the reverse of the given domain's canonical domain.
     *
     * @param  string  $domain
     * @return string
     */
    public function nonCanonicalDomain($domain)
    {
        $canonical = $this->canonicalDomain($domain);

        if (Str::startsWith($canonical, 'www.')) {
            return Str::replaceFirst('www.', '', $canonical);
        }

        if (substr_count($canonical, '.') >= 2) {
            return $canonical;
        }

        return 'www.'.$canonical;
    }

    /**
     * Get all of the stack's deployments.
     */
    public function deployments()
    {
        return $this->hasMany(Deployment::class)->latest('id');
    }

    /**
     * Get the last deployment for the stack.
     *
     * @return \App\Deployment|null
     */
    public function lastDeployment()
    {
        return $this->deployments->first();
    }

    /**
     * Determine if the stack is deploying.
     *
     * @return bool
     */
    public function isDeploying()
    {
        return $this->deployment_status == 'deploying';
    }

    /**
     * Deploy fresh code using the deployment instructions.
     *
     * @param  \App\DeploymentInstructions  $instructions
     * @return \App\Deployment
     */
    public function deployUsing(DeploymentInstructions $instructions)
    {
        return $this->deployHash(
            $instructions->hash,
            $instructions->build, $instructions->activate,
            $instructions->directories, $instructions->daemons,
            $instructions->schedule
        );
    }

    /**
     * Deploy fresh code to the stack.
     *
     * @param  string  $hash
     * @param  array  $build
     * @param  array  $activate
     * @param  string  $hash
     * @param  array  $directories
     * @param  array  $daemons
     * @param  array  $schedule
     * @return \App\Deployment
     */
    public function deploy($hash, array $build = [], array $activate = [],
                           array $directories = [], array $daemons = [],
                           array $schedule = [])
    {
        // First we will make sure we can actually deploy this stack. If the stack is not
        // provisioned or we cannot obtain a lock, we will return out since we are not
        // able to safely deploy to the stack. Otherwise, we can keep on going here.
        if ($this->isDeploying() || ! $this->deploymentLock()->get()) {
            throw new AlreadyDeployingException;
        }

        $this->markAsDeploying();

        // We will create a deployment record and start building this deployment, as well
        // monitor it for its progress. This will update the deployment status as this
        // deployment progresses as well as time it out if this never actually ends.
        $deployment = $this->createDeployment(
            $hash, $build, $activate,
            $directories, $daemons, $schedule
        );

        return tap(tap($deployment, function ($deployment) {
            $deployment->monitor();
        }))->build();
    }

    /**
     * Deploy the given branch to the stack.
     *
     * @param  string  $branch
     * @param  array  $build
     * @param  array  $activate
     * @param  array  $directories
     * @param  array  $daemons
     * @param  array  $schedule
     * @return \App\Deployment
     */
    public function deployBranch($branch, array $build = [], array $activate = [],
                                 array $directories = [], array $daemons = [],
                                 array $schedule = [])
    {
        $hash = $this->project()->sourceProvider->client()->latestHashFor(
            $this->project()->repository, $branch
        );

        return tap($this->deploy(
            $hash, $build, $activate, $directories, $daemons, $schedule
        ))->update([
            'branch' => $branch
        ]);
    }

    /**
     * Deploy the given hash to the stack.
     *
     * @param  string  $hash
     * @param  array  $build
     * @param  array  $activate
     * @param  array  $directories
     * @param  array  $daemons
     * @param  array  $schedule
     * @return \App\Deployment
     */
    public function deployHash($hash, array $build = [], array $activate = [],
                               array $directories = [], array $daemons = [],
                               array $schedule = [])
    {
        return $this->deploy(
            $hash, $build, $activate, $directories, $daemons, $schedule
        );
    }

    /**
     * Mark the stack as deploying.
     *
     * @return void
     */
    public function markAsDeploying()
    {
        $this->update([
            'deployment_status' => 'deploying',
            'deployment_started_at' => Carbon::now(),
        ]);
    }

    /**
     * Create a new deployment record for the stack.
     *
     * @param  string  $hash
     * @param  array  $build
     * @param  array  $activate
     * @param  array  $directories
     * @param  array  $daemons
     * @param  array  $schedule
     * @return \App\Deployment
     */
    protected function createDeployment($hash, array $build = [], array $activate = [],
                                        array $directories = [], array $daemons = [],
                                        array $schedule = [])
    {
        return tap($this->deployments()->create([
            'commit_hash' => $hash,
            'build_commands' => $build,
            'activation_commands' => $activate,
            'directories' => collect($directories)->map(function ($directory) {
                return trim($directory, '/');
            })->all(),
            'daemons' => $daemons,
            'schedule' => $schedule,
            'status' => 'pending',
        ]), function () {
            $this->trimDeployments();
        });
    }

    /**
     * Trim the total deployments for the stack.
     *
     * @return void
     */
    protected function trimDeployments()
    {
        $deployments = $this->deployments()->get();

        if (count($deployments) > 20) {
            $deployments->slice(20 - count($deployments))->each->delete();
        }
    }

    /**
     * Determine if the stack has a pending deployment.
     *
     * @return bool
     */
    public function hasPendingDeployment()
    {
        return ! empty($this->pending_deployment);
    }

    /**
     * Store the information for a pending deployment.
     *
     * @param  \App\Hook  $hook
     * @param  string  $hash
     * @return void
     */
    public function storePendingDeployment(Hook $hook, $hash)
    {
        $this->update([
            'pending_deployment' => [
                'hook_id' => $hook->id,
                'commit_hash' => $hash,
            ],
        ]);
    }

    /**
     * Deploy the pending hook deployment if applicable.
     *
     * @return \App\Deployment
     */
    public function deployPending()
    {
        if (! $this->hasPendingDeployment() ||
            ! $hook = Hook::find($this->pending_deployment['hook_id'])) {
            return $this->resetPendingDeployment();
        }

        try {
            $instructions = $this->pendingCommitHash()
                    ? DeploymentInstructions::fromHookCommit($hook, $this->pendingCommitHash())
                    : DeploymentInstructions::forLatestHookCommit($hook);

            return $this->deployUsing($instructions);
        } catch (Exception $e) {
            report($e);
        } finally {
            $this->resetPendingDeployment();
        }
    }

    /**
     * Get the pending deployment's commit hash.
     *
     * @return string|null
     */
    protected function pendingCommitHash()
    {
        return $this->pending_deployment['commit_hash'] ?? null;
    }

    /**
     * Reset the pending deployment information.
     *
     * @return void
     */
    protected function resetPendingDeployment()
    {
        $this->update(['pending_deployment' => []]);
    }

    /**
     * Reset the deployment status for the stack.
     *
     * @return void
     */
    public function resetDeploymentStatus()
    {
        $this->deploymentLock()->release();

        $this->update([
            'deployment_status' => null,
            'deployment_started_at' => null,
        ]);
    }

    /**
     * Get a deployment lock instance for the stack.
     *
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function deploymentLock()
    {
        return Cache::store('redis')->lock('deploy:'.$this->id, 60);
    }

    /**
     * Define the stack using the given definition.
     *
     * @param  \App\Environment  $environment
     * @param  \App\Contracts\StackDefinition  $definition
     * @return $this
     */
    public static function createForEnvironment(Environment $environment,
                                                StackDefinition $definition)
    {
        $project = $definition->project();

        $stack = $environment->stacks()->create([
            'creator_id' => $definition->creator()->id,
            'name' => $definition['name'],
            'url' => Haiku::withToken(),
            'pending_deployment' => [],
            'meta' => [
                'php' => '7.1',
                'initial_branch' => $definition['branch'],
                'initial_build_commands' => $definition['build'] ?? [],
                'initial_activation_commands' => $definition['activate'] ?? [],
                'initial_directories' => $definition['directories'] ?? [],
                'initial_daemons' => $definition->daemons(),
                'initial_schedule' => $definition->schedule(),
                'scripts' => $definition->scripts(),
            ],
        ]);

        $stack->createServerRecords($definition);

        $databases = collect($definition['databases']);

        $stack->databases()->sync($databases->map(function ($name) use ($project) {
            return $project->databases->where('name', $name)->first()->id;
        })->all());

        return $stack;
    }

    /**
     * Create the server records for the stack.
     *
     * @param  \App\Contracts\StackDefinition  $definition
     * @return $this
     */
    protected function createServerRecords(StackDefinition $definition)
    {
        (new AppServerRecordCreator($this, $definition))->create();
        (new WebServerRecordCreator($this, $definition))->create();
        (new WorkerServerRecordCreator($this, $definition))->create();

        return $this;
    }

    /**
     * Determine if the stack is provisioned.
     *
     * @return bool
     */
    public function isProvisioned()
    {
        return $this->status == 'provisioned';
    }

    /**
     * Provision the stack.
     *
     * @return $this
     */
    public function provision()
    {
        if ($this->status == 'provisioning') {
            return;
        }

        $this->update([
            'status' => 'provisioning'
        ]);

        StackProvisioning::dispatch($this);

        Jobs\CreateLoadBalancerIfNecessary::dispatch($this)->chain([
            new Jobs\ProvisionServers($this),
            new Jobs\WaitForServersToFinishProvisioning($this),
            new Jobs\SyncStackNetwork($this),
            new Jobs\WaitForStackToFinishNetworking($this),
            new Jobs\InstallRepository($this),
            new Jobs\WaitForRepositoryInstallation($this),
            new Jobs\SyncBalancers($this->environment->project),
            new Jobs\AddDnsRecord($this),
            new Jobs\WaitForDnsRecordToPropagate($this),
            new Jobs\MarkStackAsProvisioned($this),
        ]);

        return $this;
    }

    /**
     * Mark the stack as provisioned.
     *
     * @return void
     */
    public function markAsProvisioned()
    {
        $this->update([
            'status' => 'provisioned',
        ]);

        StackProvisioned::dispatch($this);
    }

    /**
     * Get the PHP version for the stack.
     *
     * @return string
     */
    public function phpVersion()
    {
        return $this->meta['php'];
    }

    /**
     * Delete the model from the database.
     *
     * @return bool|null
     *
     * @throws \Exception
     */
    public function delete()
    {
        StackDeleting::dispatch($this);

        $this->balancers()->each->sync(10);

        if ($this->dns_address) {
            Jobs\DeleteDnsRecord::dispatch(
                $this->url, $this->dns_address
            );
        }

        $this->databases->each->syncNetwork(5);
        $this->databases()->detach();

        rescue(function () {
            $this->hooks->each->unpublish();
        });

        $this->hooks()->delete();

        $this->tasks()->delete();
        $this->deployments()->delete();
        $this->allServers()->each->delete();

        parent::delete();
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge(parent::toArray(), [
            'entrypoint' => $this->entrypoint(),
            'last_deployment' => $this->lastDeployment(),
        ]);
    }
}
