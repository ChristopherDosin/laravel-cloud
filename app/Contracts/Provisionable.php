<?php

namespace App\Contracts;

use App\User;
use App\Scripts\Script;

interface Provisionable
{
    /**
     * Get the project ID for the server.
     *
     * @return int
     */
    public function projectId();

    /**
     * Get the project that owns the server.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function project();

    /**
     * Get the IP address information for the server.
     */
    public function address();

    /**
     * Get the tasks that belong to the server.
     */
    public function tasks();

    /**
     * Get the server's provider server ID.
     *
     * @return string
     */
    public function providerServerId();

    /**
     * Get the IP address for the server.
     *
     * @return string
     */
    public function ipAddress();

    /**
     * Get the private IP address for the server.
     *
     * @return string
     */
    public function privateIpAddress();

    /**
     * Get the SSH port for the server.
     *
     * @return string
     */
    public function port();

    /**
     * Get the size of the server in megabytes.
     *
     * @return int
     */
    public function sizeInMegabytes();

    /**
     * Determine if the given user can SSH into the server.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function canSsh(User $user);

    /**
     * Get the path to the server owner's worker SSH key.
     *
     * @return string
     */
    public function ownerKeyPath();

    /**
     * Determine if the server is ready for provisioning.
     *
     * @return bool
     */
    public function isReadyForProvisioning();

    /**
     * Determine if the server is currently provisioning.
     *
     * @return bool
     */
    public function isProvisioning();

    /**
     * Mark the server as provisioning.
     *
     * @return $this
     */
    public function markAsProvisioning();

    /**
     * Determine if the server is currently provisioned.
     *
     * @return bool
     */
    public function isProvisioned();

    /**
     * Mark the server as provisioned.
     *
     * @return $this
     */
    public function markAsProvisioned();

    /**
     * Dispatch the job to provision the server.
     *
     * @return void
     */
    public function provision();

    /**
     * Run the provisioning script on the server.
     *
     * @return \App\Task|null
     */
    public function runProvisioningScript();

    /**
     * Determine if the provisioning job has been dispatched.
     *
     * @return bool
     */
    public function provisioningJobDispatched();

    /**
     * Run the given script on the server.
     *
     * @param  Script  $script
     * @param  array  $options
     * @return Task
     */
    public function run(Script $script, array $options = []);

    /**
     * Run the given script in the background the server.
     *
     * @param  \App\Scripts\Script  $script
     * @param  array  $options
     * @return Task
     */
    public function runInBackground(Script $script, array $options = []);
}
