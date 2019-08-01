<?php

namespace App;

use App\Scripts\Script;
use Facades\App\TaskFactory;
use App\Scripts\GetAptLockStatus;
use App\Scripts\GetCurrentDirectory;

trait Provisionable
{
    use DeterminesAge;

    /**
     * Get the project ID for the server.
     *
     * @return int
     */
    public function projectId()
    {
        return $this->project->id;
    }

    /**
     * Get the project that owns the server.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the IP address information for the server.
     */
    public function address()
    {
        return $this->morphOne(IpAddress::class, 'addressable');
    }

    /**
     * Get the tasks that belong to the server.
     */
    public function tasks()
    {
        return $this->morphMany(Task::class, 'provisionable');
    }

    /**
     * Get the server's provider server ID.
     *
     * @return string
     */
    public function providerServerId()
    {
        return $this->provider_server_id;
    }

    /**
     * Get the IP address for the server.
     *
     * @return string
     */
    public function ipAddress()
    {
        return $this->address ? $this->address->public_address : null;
    }

    /**
     * Get the private IP address for the server.
     *
     * @return string
     */
    public function privateIpAddress()
    {
        return $this->address ? $this->address->private_address : null;
    }

    /**
     * Get the SSH port for the server.
     *
     * @return string
     */
    public function port()
    {
        return $this->port;
    }

    /**
     * Get the size of the server in megabytes.
     *
     * @return int
     */
    public function sizeInMegabytes()
    {
        return $this->project->withProvider()->sizeInMegabytes($this->size);
    }

    /**
     * Get the path to the server owner's worker SSH key.
     *
     * @return string
     */
    public function ownerKeyPath()
    {
        return $this->project->user->keyPath();
    }

    /**
     * Set the SSH key attributes on the model.
     *
     * @param  object  $value
     * @return void
     */
    public function setKeypairAttribute($value)
    {
        $this->attributes = [
            'public_key' => $value->publicKey,
            'private_key' => $value->privateKey,
        ] + $this->attributes;
    }

    /**
     * Determine if the server is ready for provisioning.
     *
     * @return bool
     */
    public function isReadyForProvisioning()
    {
        if (! $this->ipAddress()) {
            $this->retrieveIpAddresses();
        }

        $canAccess = $this->fresh()->ipAddress() && $this->run(
            new GetCurrentDirectory
        )->output == '/root';

        if ($canAccess) {
            $apt = $this->run(new GetAptLockStatus);
        } else {
            return false;
        }

        return $apt->successful() &&
               $apt->output === '';
    }

    /**
     * Attempt to retrieve and store the server's IP addresses.
     *
     * @return void
     */
    protected function retrieveIpAddresses()
    {
        $project = $this->project;

        list($publicIp, $privateIp) = [
            $project->withProvider()->getPublicIpAddress($this),
            $project->withProvider()->getPrivateIpAddress($this),
        ];

        if (! $publicIp || ! $privateIp) {
            return;
        }

        $this->address()->create([
            'public_address' => $publicIp,
            'private_address' => $privateIp,
        ]);
    }

    /**
     * Determine if the server is currently provisioning.
     *
     * @return bool
     */
    public function isProvisioning()
    {
        return $this->status == 'provisioning';
    }

    /**
     * Mark the server as provisioning.
     *
     * @return $this
     */
    public function markAsProvisioning()
    {
        return tap($this)->update(['status' => 'provisioning']);
    }

    /**
     * Determine if the server is currently provisioned.
     *
     * @return bool
     */
    public function isProvisioned()
    {
        return $this->status == 'provisioned';
    }

    /**
     * Mark the server as provisioned.
     *
     * @return $this
     */
    public function markAsProvisioned()
    {
        return tap($this)->update(['status' => 'provisioned']);
    }

    /**
     * Determine if the provisioning job has been dispatched.
     *
     * @return bool
     */
    public function provisioningJobDispatched()
    {
        return ! is_null($this->provisioning_job_dispatched_at);
    }

    /**
     * Run the given script on the server.
     *
     * @param  \App\Scripts\Script  $script
     * @param  array  $options
     * @return Task
     */
    public function run(Script $script, array $options = [])
    {
        if (! array_key_exists('timeout', $options)) {
            $options['timeout'] = $script->timeout();
        }

        return $this->tasks()->create([
            'project_id' => $this->projectId(),
            'name' => $script->name(),
            'user' => $script->sshAs,
            'options' => $options,
            'script' => (string) $script,
            'output' => '',
        ])->run();
    }

    /**
     * Run the given script in the background the server.
     *
     * @param  \App\Scripts\Script  $script
     * @param  array  $options
     * @return Task
     */
    public function runInBackground(Script $script, array $options = [])
    {
        return TaskFactory::createFromScript(
            $this, $script, $options
        )->runInBackground();
    }
}
