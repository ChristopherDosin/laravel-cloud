<?php

namespace App\Scripts;

class PauseDaemon extends DaemonScript
{
    /**
     * Get the name of the script.
     *
     * @return string
     */
    public function name()
    {
        return "Pausing Daemons ({$this->deployment->deployable->name})";
    }

    /**
     * Get the name of the script to run.
     *
     * @return string
     */
    public function scriptName()
    {
        return 'scripts.daemons.pause';
    }
}
