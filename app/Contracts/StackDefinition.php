<?php

namespace App\Contracts;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;

interface StackDefinition extends ArrayAccess, Arrayable
{
    /**
     * Get the user that is creating the stack.
     *
     * @return \App\User
     */
    public function creator();

    /**
     * Get the project associated with the request.
     *
     * @return \App\Project
     */
    public function project();

    /**
     * Extract the daemons from the request.
     *
     * @return array
     */
    public function daemons();

    /**
     * Extract the scripts from the request.
     *
     * @return array
     */
    public function scripts();
}
