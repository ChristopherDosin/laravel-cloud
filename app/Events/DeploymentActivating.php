<?php

namespace App\Events;

use App\Deployment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class DeploymentActivating
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
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
