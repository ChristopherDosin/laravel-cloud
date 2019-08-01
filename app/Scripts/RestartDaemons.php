<?php

namespace App\Scripts;

class RestartDaemons extends DaemonScript
{
    /**
     * Get the name of the script.
     *
     * @return string
     */
    public function name()
    {
        return "Restarting Daemons ({$this->deployment->deployable->name})";
    }

    /**
     * Get the name of the script to run.
     *
     * @return string
     */
    public function scriptName()
    {
        return 'scripts.daemon.restart';
    }

    /**
     * Get the daemon configuration script.
     *
     * @return string
     */
    public function daemonConfiguration()
    {
        return view('scripts.daemon.build', [
            'script' => $this,
            'deployment' => $this->deployment,
            'generation' => $this->deployment->currentDaemonGeneration(),
        ])->render();
    }

    /**
     * Get the daemon activation script.
     *
     * @return string
     */
    public function activateDaemons()
    {
        return view('scripts.daemon.activate', [
            'script' => $this,
            'generation' => $this->deployment->currentDaemonGeneration(),
            'previousGenerations' => $this->deployment->previousDaemonGenerations(),
        ])->render();
    }
}
