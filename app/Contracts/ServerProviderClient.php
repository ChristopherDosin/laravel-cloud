<?php

namespace App\Contracts;

interface ServerProviderClient
{
    /**
     * Determine if the provider credentials are valid.
     *
     * @return bool
     */
    public function valid();

    /**
     * Get all of the valid regions for the provider.
     *
     * @return array
     */
    public function regions();

    /**
     * Get all of the valid server sizes for the provider.
     *
     * @return array
     */
    public function sizes();

    /**
     * Get the size of the server in megabytes.
     *
     * @param  string  $size
     * @return int
     */
    public function sizeInMegabytes($size);

    /**
     * Get the recommended balancer size for a given server size.
     *
     * @param  string  $size
     * @return string
     */
    public function recommendedBalancerSize($size);

    /**
     * Create a new server.
     *
     * @param  string  $name
     * @param  string  $size
     * @param  string  $region
     * @return string
     */
    public function createServer($name, $size, $region);

    /**
     * Get the public IP address for a server by ID.
     *
     * @param  \App\Contracts\Provisionable  $server
     * @return string|null
     */
    public function getPublicIpAddress(Provisionable $server);

    /**
     * Get the private IP address for a server by ID.
     *
     * @param  \App\Contracts\Provisionable  $server
     * @return string|null
     */
    public function getPrivateIpAddress(Provisionable $server);

    /**
     * Delete the given server.
     *
     * @param  \App\Contracts\Provisionable  $server
     * @return void
     */
    public function deleteServer(Provisionable $server);

    /**
     * Delete the given server by ID.
     *
     * @param  string  $providerServerId
     * @return void
     */
    public function deleteServerById($providerServerId);
}
