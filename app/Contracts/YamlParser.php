<?php

namespace App\Contracts;

interface YamlParser
{
    /**
     * Parse the given YAML into an array.
     *
     * @param  string  $yaml
     * @return array
     */
    public function parse($yaml);
}
