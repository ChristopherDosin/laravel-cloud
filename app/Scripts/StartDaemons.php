<?php

namespace App\Scripts;

class StartDaemons extends DaemonScript
{
    /**
     * Get the name of the script.
     *
     * @return string
     */
    public function name()
    {
        return "Starting Daemons ({$this->deployment->deployable->name})";
    }

    /**
     * Get the name of the script to run.
     *
     * @return string
     */
    public function scriptName()
    {
        return 'scripts.daemon.start';
    }
}
