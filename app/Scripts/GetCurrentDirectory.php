<?php

namespace App\Scripts;

class GetCurrentDirectory extends Script
{
    /**
     * The displayable name of the script.
     *
     * @var string
     */
    public $name = 'Echoing Current Directory';

    /**
     * Get the contents of the script.
     *
     * @return string
     */
    public function script()
    {
        return 'pwd';
    }

    /**
     * Get the timeout for the script.
     *
     * @return int|null
     */
    public function timeout()
    {
        return 10;
    }
}
