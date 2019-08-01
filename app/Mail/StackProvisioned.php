<?php

namespace App\Mail;

use App\Stack;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StackProvisioned extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The stack instance.
     *
     * @var \App\Stack
     */
    public $stack;

    /**
     * Create a new message instance.
     *
     * @param  \App\Stack  $stack
     * @return void
     */
    public function __construct(Stack $stack)
    {
        $this->stack = $stack;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Stack Created')
                    ->markdown('mail.stack.provisioned', [
                        'stack' => $this->stack,
                    ]);
    }
}
