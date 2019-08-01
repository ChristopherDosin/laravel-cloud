<?php

namespace App\Events;

use App\Deployment;
use App\Contracts\HasStack;
use App\Contracts\Alertable;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DeploymentFinished implements Alertable, HasStack, ShouldBroadcast
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
            'level' => 'success',
            'type' => 'DeploymentFinished',
            'exception' => '',
            'meta' => [
                'deployment_id' => $this->deployment->id,
                'repository' => $this->deployment->repository(),
                'commit_hash' => $this->deployment->commit_hash,
            ],
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new Channel(
            'stack.'.$this->deployment->stack->id
        );
    }
}
