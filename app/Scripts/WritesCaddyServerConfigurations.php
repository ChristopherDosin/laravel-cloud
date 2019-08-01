<?php

namespace App\Scripts;

use App\CaddyServerConfiguration;

trait WritesCaddyServerConfigurations
{
    /**
     * Get the Caddy server configuration for the actual domains.
     *
     * @return string
     */
    public function actualDomainConfiguration()
    {
        if (empty($this->server->actualDomainsWithPorts())) {
            return '';
        }

        return collect($this->server->actualDomainsWithPorts())->map(function ($domain) {
            return new CaddyServerConfiguration($this->server, $domain);
        })->map->render()->implode(PHP_EOL);
    }

    /**
     * Get the Caddy server configuration for the vanity domains.
     *
     * @return string
     */
    public function vanityDomainConfiguration()
    {
        return collect($this->server->vanityDomainsWithPorts())->map(function ($domain) {
            return new CaddyServerConfiguration($this->server, $domain);
        })->map->render()->implode(PHP_EOL);
    }
}
