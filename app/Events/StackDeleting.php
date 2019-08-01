<?php

namespace App\Events;

use App\Stack;
use App\Contracts\Alertable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class StackDeleting implements Alertable
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The stack instance.
     *
     * @var \App\Stack
     */
    public $stack;

    /**
     * Create a new event instance.
     *
     * @param  \App\Stack  $stack
     * @return void
     */
    public function __construct(Stack $stack)
    {
        $this->stack = $stack;
    }

    /**
     * Create an alert for the given instance.
     *
     * @return \App\Alert
     */
    public function toAlert()
    {
        return $this->stack->project()->alerts()->create([
            'stack_id' => $this->stack->id,
            'level' => 'info',
            'type' => 'StackDeleted',
            'exception' => '',
            'meta' => [],
        ]);
    }
}
