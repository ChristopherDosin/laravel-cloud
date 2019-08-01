<?php

namespace App\Scripts;

class StopDaemons extends DaemonScript
{
    /**
     * Get the name of the script.
     *
     * @return string
     */
    public function name()
    {
        return "Stopping Daemons ({$this->deployment->deployable->name})";
    }

    /**
     * Get the name of the script to run.
     *
     * @return string
     */
    public function scriptName()
    {
        return 'scripts.daemon.stop';
    }
}
