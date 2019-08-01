<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use App\ServerProvider;
use InvalidArgumentException;
use App\Contracts\Provisionable;
use App\Contracts\ServerProviderClient;

class DigitalOcean implements ServerProviderClient
{
    /**
     * The server provider instance.
     *
     * @var \App\ServerProvider
     */
    protected $provider;

    /**
     * Create a new DigitalOcean service instance.
     *
     * @param  \App\ServerProvider  $provider
     * @return void
     */
    public function __construct(ServerProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Determine if the provider credentials are valid.
     *
     * @return bool
     */
    public function valid()
    {
        try {
            $this->request('get', '/regions');

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get all of the valid regions for the provider.
     *
     * @return array
     */
    public function regions()
    {
        return [
            'ams2' => 'Amsterdam 2',
            'ams3' => 'Amsterdam 3',
            'blr1' => 'Bangalore',
            'lon1' => 'London',
            'fra1' => 'Frankfurt',
            'nyc1' => 'New York 1',
            'nyc2' => 'New York 2',
            'nyc3' => 'New York 3',
            'sfo1' => 'San Francisco 1',
            'sfo2' => 'San Francisco 2',
            'sgp1' => 'Singapore',
            'tor1' => 'Toronto',
        ];
    }

    /**
     * Get all of the valid server sizes for the provider.
     *
     * @return array
     */
    public function sizes()
    {
        return [
            '512MB' => ['cpu' => '1 Core', 'ram' => '512MB', 'hdd' => '20GB', 'price' => 5],
            '1GB' => ['cpu' => '1 Core', 'ram' => '1GB', 'hdd' => '30GB', 'price' => 10],
            '2GB' => ['cpu' => '2 Core', 'ram' => '2GB', 'hdd' => '40GB', 'price' => 20],
            '4GB' => ['cpu' => '2 Core', 'ram' => '4GB', 'hdd' => '60GB', 'price' => 40],
            '8GB' => ['cpu' => '4 Core', 'ram' => '8GB', 'hdd' => '80GB', 'price' => 80],
            '16GB' => ['cpu' => '8 Core', 'ram' => '16GB', 'hdd' => '160GB', 'price' => 160],
            'm16GB' => ['cpu' => '2 Core', 'ram' => '16GB (High Memory)', 'hdd' => '30GB', 'price' => 120],
            '32GB' => ['cpu' => '12 Core', 'ram' => '32GB', 'hdd' => '320GB', 'price' => 320],
            'm32GB' => ['cpu' => '4 Core', 'ram' => '32GB (High Memory)', 'hdd' => '90GB', 'price' => 240],
            '64GB' => ['cpu' => '20 Core', 'ram' => '64GB', 'hdd' => '640GB', 'price' => 640],
            'm64GB' => ['cpu' => '8 Core', 'ram' => '64GB (High Memory)', 'hdd' => '200GB', 'price' => 480],
        ];
    }

    /**
     * Get the size of the server in megabytes.
     *
     * @param  string  $size
     * @return int
     */
    public function sizeInMegabytes($size)
    {
        switch ($size) {
            case '512MB':
                return 512;
            case '1GB':
                return 1024;
            case '2GB':
                return 2048;
            case '4GB':
                return 4096;
            case '8GB':
                return 8192;
            case '16GB':
                return 16384;
            case 'm16GB':
                return 16384 + 1;
            case '32GB':
                return 32768;
            case 'm32GB':
                return 32768 + 1;
            case '64GB':
                return 65536;
            case 'm64GB':
                return 65536 + 1;
        }

        throw new InvalidArgumentException("Invalid size.");
    }

    /**
     * Get the recommended balancer size for a given server size.
     *
     * @param  string  $size
     * @return string
     */
    public function recommendedBalancerSize($size)
    {
        switch ($size) {
            case '512MB':
            case '1GB':
                return '512MB';
            case '2GB':
                return '1GB';
            case '4GB':
            case '8GB':
                return '2GB';
            case '16GB':
            case 'm16GB':
            case '32GB':
            case 'm32GB':
                return '4GB';
            case '64GB':
            case 'm64GB':
                return '8GB';
        }

        throw new InvalidArgumentException("Invalid size.");
    }

    /**
     * Create a new server.
     *
     * @param  string  $name
     * @param  string  $size
     * @param  string  $region
     * @return string
     */
    public function createServer($name, $size, $region)
    {
        return $this->request('post', '/droplets', [
            'name' => $name,
            'size' => $size,
            'region' => $region,
            'image' => 'ubuntu-17-04-x64',
            'ipv6' => true,
            'private_networking' => true,
            'ssh_keys' => [$this->keyId()],
            'monitoring' => true,
        ])['droplet']['id'];
    }

    /**
     * Get the SSH key ID for our SSH key.
     *
     * @return int
     */
    public function keyId()
    {
        return tap($this->findKey()['id'] ?? $this->addKey(), function ($id) {
            $this->provider->user->update([
                'provider_key_id' => $id,
            ]);
        });
    }

    /**
     * Attempt to find our SSH key on the DigitalOcean account.
     *
     * @return array|null
     */
    public function findKey()
    {
        if ($id = $this->provider->user->provider_key_id) {
            return $this->request('get', '/account/keys/'.$id)['ssh_key'];
        }

        return collect($this->aggregate('get', '/account/keys', 'ssh_keys'))->first(function ($key) {
            return $key['public_key'] == trim($this->provider->user->public_worker_key);
        });
    }

    /**
     * Add our SSH key to the DigitalOcean account.
     *
     * @return int
     */
    public function addKey()
    {
        return $this->request('post', '/account/keys', [
            'name' => 'Laravel Cloud',
            'public_key' => $this->provider->user->public_worker_key,
        ])['ssh_key']['id'];
    }

    /**
     * Remove our SSH key from the account.
     *
     * @return void
     */
    public function removeKey()
    {
        if ($id = $this->keyId()) {
            $this->request('delete', '/account/keys/'.$id);

            $this->provider->user->update([
                'provider_key_id' => null,
            ]);
        }
    }

    /**
     * Get the public IP address for a server by ID.
     *
     * @param  \App\Contracts\Provisionable  $server
     * @return string|null
     */
    public function getPublicIpAddress(Provisionable $server)
    {
        return $this->getIpAddress($server);
    }

    /**
     * Get the private IP address for a server by ID.
     *
     * @param  \App\Contracts\Provisionable  $server
     * @return string|null
     */
    public function getPrivateIpAddress(Provisionable $server)
    {
        return $this->getIpAddress($server, 'private');
    }

    /**
     * Delete the given server.
     *
     * @param  \App\Contracts\Provisionable  $server
     * @return void
     */
    public function deleteServer(Provisionable $server)
    {
        $this->deleteServerById($server->providerServerId());
    }

    /**
     * Delete the given server.
     *
     * @param  string  $providerServerId
     * @return void
     */
    public function deleteServerById($providerServerId)
    {
        $this->request('delete', "/droplets/{$providerServerId}");
    }

    /**
     * Get an IP address for the server.
     *
     * @param  \App\Contracts\Provisionable  $server
     * @param  string  $type
     * @return string|null
     */
    protected function getIpAddress(Provisionable $server, $type = 'public')
    {
        $networks = $this->request(
            'get', "/droplets/{$server->providerServerId()}"
        )['droplet']['networks']['v4'] ?? [];

        return collect($networks)->filter(function ($network) use ($type) {
            return ($network['type'] ?? null) == $type;
        })->first()['ip_address'] ?? null;
    }

    /**
     * Make an HTTP request to DigitalOcean.
     *
     * @param  string  $method
     * @param  string  $path
     * @param  array  $parameters
     * @return array
     */
    protected function request($method, $path, array $parameters = [])
    {
        $response = (new Client)->{$method}('https://api.digitalocean.com/v2/'.ltrim($path, '/'), [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->provider->meta['token']
            ],
            'json' => $parameters,
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Aggregate pages of results into a single result array.
     *
     * @param  string  $method
     * @param  string  $path
     * @param  array  $target
     * @param  array  $parameters
     * @return array
     */
    protected function aggregate($method, $path, $target, array $parameters = [])
    {
        $page = 1;

        $results = [];

        do {
            $response = $this->request(
                $method, $path.'?page='.$page.'&per_page=100', $parameters
            );

            $results = array_merge($results, $response[$target]);

            $page++;
        } while (isset($response['links']['pages']['next']));

        return $results;
    }
}
