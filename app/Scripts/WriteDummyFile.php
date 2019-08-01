<?php

namespace App\Scripts;

class WriteDummyFile extends Script
{
    /**
     * The displayable name of the script.
     *
     * @var string
     */
    public $name = 'Writing Dummy File';

    /**
     * Get the contents of the script.
     *
     * @return string
     */
    public function script()
    {
        return 'echo "Hello World" > /root/dummy';
    }
}
