<?php

namespace App\Scripts;

use App\Task;

class Script
{
    /**
     * The user that the script should be run as.
     *
     * @var string
     */
    public $sshAs = 'root';

    /**
     * The random token used for heredoc delimiting.
     *
     * @var string
     */
    protected static $token;

    /**
     * Get the name of the script.
     *
     * @return string
     */
    public function name()
    {
        return $this->name ?? '';
    }

    /**
     * Get the timeout for the script.
     *
     * @return int|null
     */
    public function timeout()
    {
        return Task::DEFAULT_TIMEOUT;
    }

    /**
     * Render the script as a string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->script();
    }
}
