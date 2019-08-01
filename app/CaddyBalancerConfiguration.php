<?php

namespace App;

use Illuminate\Support\Str;

class CaddyBalancerConfiguration
{
    /**
     * The balancer instance.
     *
     * @var \App\Balancer
     */
    public $balancer;

    /**
     * The stack instance.
     *
     * @var \App\Stack
     */
    public $stack;

    /**
     * The domain name.
     *
     * @var string
     */
    public $domain;

    /**
     * The addresses the balancer should proxy to.
     *
     * @var array
     */
    public $proxyTo;

    /**
     * Create a new Caddy configuration instance.
     *
     * @param  \App\Balancer  $balancer
     * @param  \App\Stack  $balancer
     * @param  string  $domain
     * @param  array  $proxyTo
     * @return void
     */
    public function __construct(Balancer $balancer, Stack $stack, $domain, array $proxyTo)
    {
        $this->stack = $stack;
        $this->domain = $domain;
        $this->proxyTo = $proxyTo;
        $this->balancer = $balancer;
    }

    /**
     * Render the Caddy configuration block.
     *
     * @return string
     */
    public function render()
    {
        return view($this->script(), [
            'canonicalDomain' => $this->stack->canonicalDomain($this->domain),
            'domain' => $this->domain,
            'tls' => $this->tls(),
            'proxyTo' => $this->proxyTo,
        ])->render();
    }

    /**
     * Get the script that should be used to build the configuration.
     *
     * @return string
     */
    protected function script()
    {
        return ! $this->stack->isCanonicalDomain($this->domain) || Str::contains($this->domain, ':80')
                        ? 'scripts.caddy-configuration.redirect'
                        : 'scripts.caddy-configuration.proxy';
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

        if ($this->balancer->selfSignsCertificates()) {
            return 'tls self_signed';
        }

        return 'tls {
        max_certs 1
    }';
    }
}
