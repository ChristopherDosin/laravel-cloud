<?php

namespace App;

class ShellResponse
{
    /**
     * The exit code of the command.
     *
     * @var int
     */
    public $exitCode;

    /**
     * The output of the command.
     *
     * @var string
     */
    public $output;

    /**
     * Indicates if the command timed out.
     *
     * @var bool
     */
    public $timedOut = false;

    /**
     * Create a new response instance.
     *
     * @param  int  $exitCode
     * @param  string  $output
     * @param  bool  $timedOut
     * @return void
     */
    public function __construct($exitCode, $output, $timedOut = false)
    {
        $this->output = $output;
        $this->exitCode = $exitCode;
        $this->timedOut = $timedOut;
    }
}
