<?php

namespace App;

use App\Jobs\SyncServer;
use Illuminate\Support\Str;

abstract class HttpServer extends Server
{
    /**
     * Get the array of domains the server should respond to.
     *
     * @return array
     */
    public function shouldRespondTo()
    {
        return collect($this->meta['serves'] ?? [])->flatMap(function ($domain) {
            return array_unique([$domain, $this->stack->nonCanonicalDomain($domain)]);
        })->merge(collect([$this->stack->url.'.laravel.build']))->all();
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
        })->unique()->values()->all();
    }

    /**
     * Get all of the server's vanity domain's with ports.
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
     * Get all of the server's vanity domain's with ports.
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
     * Refresh the server's configuration.
     *
     * @return void
     */
    public function sync()
    {
        SyncServer::dispatch($this);
    }

    /**
     * Determine if the server should self-sign TLS certificates.
     *
     * @return bool
     */
    public function selfSignsCertificates()
    {
        return ($this->meta['tls'] ?? null) === 'self-signed';
    }
}
