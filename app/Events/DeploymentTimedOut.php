<?php

namespace App\Events;

use App\Deployment;
use App\Contracts\HasStack;
use App\Contracts\Alertable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class DeploymentTimedOut implements Alertable, HasStack
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The deployment instance.
     *
     * @var \App\Deployment
     */
    public $deployment;

    /**
     * Create a new event instance.
     *
     * @param  \App\Deployment  $deployment
     * @return void
     */
    public function __construct(Deployment $deployment)
    {
        $this->deployment = $deployment;
    }

    /**
     * Get the stack instance for the object.
     *
     * @return \App\Stack
     */
    public function stack()
    {
        return $this->deployment->stack;
    }

    /**
     * Create an alert for the given instance.
     *
     * @return \App\Alert
     */
    public function toAlert()
    {
        return $this->deployment->project()->alerts()->create([
            'stack_id' => $this->deployment->stack->id,
            'type' => 'DeploymentTimedOut',
            'exception' => '',
            'meta' => [
                'deployment_id' => $this->deployment->id
            ],
        ]);
    }
}
