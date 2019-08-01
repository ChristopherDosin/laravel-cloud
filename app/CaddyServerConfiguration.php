<?php

namespace App;

use Illuminate\Support\Str;

class CaddyServerConfiguration
{
    /**
     * The server instance.
     *
     * @var \App\Server
     */
    public $server;

    /**
     * The domain name.
     *
     * @var string
     */
    public $domain;

    /**
     * Create a new Caddy configuration instance.
     *
     * @param  \App\Server  $server
     * @param  string  $domain
     * @return void
     */
    public function __construct(Server $server, $domain)
    {
        $this->server = $server;
        $this->domain = $domain;
    }

    /**
     * Render the Caddy configuration block.
     *
     * @return string
     */
    public function render()
    {
        return view($this->script(), [
            'canonicalDomain' => $this->server->stack->canonicalDomain($this->domain),
            'domain' => $this->domain,
            'root' => $this->root(),
            'tls' => $this->tls(),
            'index' => ! Str::contains($this->domain, '.laravel.build'),
        ])->render();
    }

    /**
     * Get the script that should be used to build the configuration.
     *
     * @return string
     */
    protected function script()
    {
        if (! $this->server->stack->isCanonicalDomain($this->domain) ||
            Str::contains($this->domain, ':80')) {
            return 'scripts.caddy-configuration.redirect';
        }

        return 'scripts.caddy-configuration.app';
    }

    /**
     * Get the application root directory.
     *
     * @return string
     */
    protected function root()
    {
        if ($this->server->stack->under_maintenance &&
            ! Str::contains($this->domain, 'laravel.build')) {
            return '/home/cloud/maintenance/public';
        }

        return '/home/cloud/app/public';
    }

    /**
     * Get the TLS configuration block for the script.
     *
     * @return string
     */
    protected function tls()
    {
        if (Str::contains($this->domain, ':80')) {
            return 'tls off';
        }

        if ($this->server->stack->balanced ||
            $this->server->selfSignsCertificates()) {
            return 'tls self_signed';
        }

        return 'tls {
        max_certs 1
    }';
    }
}
