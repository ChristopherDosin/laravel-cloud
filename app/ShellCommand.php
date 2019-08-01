<?php

namespace App;

use Illuminate\Support\Str;

class ShellCommand
{
    /**
     * The shell command.
     *
     * @var string
     */
    public $command;

    /**
     * Create a new shell command instance.
     *
     * @param  string  $command
     * @return void
     */
    public function __construct($command)
    {
        $this->command = $command;
    }

    /**
     * Determine if the given server can run the build command.
     *
     * @param  \App\Server  $server
     * @return bool
     */
    public function appliesTo($server)
    {
        return $server->runsCommand($this->command);
    }

    /**
     * Determine if the command is prefixed with the given false.
     *
     * @param  string  $prefix
     * @return bool
     */
    public function prefixed($prefix)
    {
        return $prefix ? Str::startsWith($this->command, $prefix) : false;
    }

    /**
     * Convert the command to a formatted string.
     *
     * @return string
     */
    public function trim()
    {
        $command = $this->command;

        $command = Str::startsWith($command, 'once:') ? Str::replaceFirst('once:', '', $command) : $command;
        $command = Str::startsWith($command, 'web:') ? Str::replaceFirst('web:', '', $command) : $command;
        $command = Str::startsWith($command, 'worker:') ? Str::replaceFirst('worker:', '', $command) : $command;

        return trim($command);
    }
}
