<?php

namespace App\Scripts;

use App\User;
use App\Contracts\Provisionable;

class RemoveKeyFromServer extends Script
{
    /**
     * The public SSH key name.
     *
     * @var string
     */
    public $name;

    /**
     * The user that the script should be run as.
     *
     * @var string
     */
    public $sshAs = 'cloud';

    /**
     * Create a new script instance.
     *
     * @param  string  $name
     * @return void
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Get the name of the script.
     *
     * @return string
     */
    public function name()
    {
        return "Removing SSH Key";
    }

    /**
     * Get the contents of the script.
     *
     * @return string
     */
    public function script()
    {
        return view('scripts.provisionable.removeKey', [
            'name' => $this->name,
        ])->render();
    }

    /**
     * Get the timeout for the script.
     *
     * @return int|null
     */
    public function timeout()
    {
        return 10;
    }
}
