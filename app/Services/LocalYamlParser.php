<?php

namespace App\Services;

use App\Contracts\YamlParser;
use Symfony\Component\Yaml\Yaml;

class LocalYamlParser implements YamlParser
{
    /**
     * Parse the given YAML into an array.
     *
     * @param  string  $yaml
     * @return array
     */
    public function parse($yaml)
    {
        return Yaml::parse($yaml);
    }
}
