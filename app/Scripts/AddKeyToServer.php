<?php

namespace App\Scripts;

use App\User;
use App\Contracts\Provisionable;

class AddKeyToServer extends Script
{
    /**
     * The public SSH key name.
     *
     * @var string
     */
    public $name;

    /**
     * The public SSH key.
     *
     * @var string
     */
    public $key;

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
     * @param  string  $key
     * @return void
     */
    public function __construct($name, $key)
    {
        $this->key = $key;
        $this->name = $name;
    }

    /**
     * Get the name of the script.
     *
     * @return string
     */
    public function name()
    {
        return "Syncing SSH Key";
    }

    /**
     * Get the contents of the script.
     *
     * @return string
     */
    public function script()
    {
        return view('scripts.provisionable.addKey', [
            'name' => $this->name,
            'key' => $this->key,
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
