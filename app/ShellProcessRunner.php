<?php

namespace App;

use Facades\App\ShellProcessRunner as Facade;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class ShellProcessRunner
{
    /**
     * Run the given process and return it.
     *
     * @param  \Symfony\Component\Process\Process  $process
     * @param  mixed  $output
     * @return ShellResponse
     */
    public function run($process)
    {
        try {
            $process = tap($process)->run($output = new ShellOutput);
        } catch (ProcessTimedOutException $e) {
            $timedOut = true;
        }

        return new ShellResponse(
            $process->getExitCode(), (string) ($output ?? ''), $timedOut ?? false
        );
    }

    /**
     * Mock the responses for the process runner.
     *
     * @param  array  $responses
     * @return void
     */
    public static function mock(array $responses)
    {
        Facade::shouldReceive('run')->andReturn(...collect($responses)->flatMap(function ($response) {
            return [
                (object) ['exitCode' => 0], // Ensure Directory Exists...
                (object) ['exitCode' => 0], // Upload...
                (object) $response,
            ];
        })->all());
    }
}
