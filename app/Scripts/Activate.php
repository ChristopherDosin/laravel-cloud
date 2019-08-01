<?php

namespace App\Scripts;

use App\ServerDeployment;

class Activate extends Script
{
    /**
     * The deployment instance.
     *
     * @var \App\ServerDeployment
     */
    public $deployment;

    /**
     * The user that the script should be run as.
     *
     * @var string
     */
    public $sshAs = 'cloud';

    /**
     * Create a new script instance.
     *
     * @param  \App\ServerDeployment  $deployment
     * @return void
     */
    public function __construct(ServerDeployment $deployment)
    {
        $this->deployment = $deployment;
    }

    /**
     * Get the name of the script.
     *
     * @return string
     */
    public function name()
    {
        return "Activating Deployment ({$this->deployment->stack()->name})";
    }

    /**
     * Get the contents of the script.
     *
     * @return string
     */
    public function script()
    {
        return view('scripts.deployment.activate', [
            'script' => $this,
            'deployment' => $this->deployment,
            'deployable' => $this->deployment->deployable,
        ])->render();
    }

    /**
     * Determine if the script should restart FPM.
     *
     * @return bool
     */
    public function shouldRestartFpm()
    {
        return ! $this->deployment->deployable->isTrueWorker();
    }

    /**
     * Get the timeout for the script.
     *
     * @return int|null
     */
    public function timeout()
    {
        return 20 * 60;
    }
}
