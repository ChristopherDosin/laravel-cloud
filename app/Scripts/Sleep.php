<?php

namespace App\Scripts;

class Sleep extends Script
{
    /**
     * The displayable name of the script.
     *
     * @var string
     */
    public $name = 'Sleeping';

    /**
     * Get the contents of the script.
     *
     * @return string
     */
    public function script()
    {
        return 'sleep 10';
    }
}
