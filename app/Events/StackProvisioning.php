<?php

namespace App\Events;

use App\Stack;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class StackProvisioning
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The stack being provisioned.
     *
     * @var Stack
     */
    public $stack;

    /**
     * Create a new event instance.
     *
     * @param  Stack  $stack
     * @return void
     */
    public function __construct(Stack $stack)
    {
        $this->stack = $stack;
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
