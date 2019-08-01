<?php

namespace Tests\Feature;

use Mockery;
use Tests\TestCase;
use App\ShellProcessRunner;
use Symfony\Component\Process\Process;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShellProcessRunnerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_process_runner_runs_process()
    {
        $process = Mockery::mock();

        $process->shouldReceive('run');
        $process->shouldReceive('getExitCode')->andReturn(0);

        $response = (new ShellProcessRunner)->run($process);

        $this->assertEquals(0, $response->exitCode);
        $this->assertEquals('', $response->output);
        $this->assertFalse($response->timedOut);
    }


    public function test_process_runner_handles_timeouts()
    {
        $process = (new Process('sleep 2'))->setTimeout(2);

        $response = (new ShellProcessRunner)->run($process);

        $this->assertEquals(0, $response->exitCode);
        $this->assertEquals('', $response->output);
        $this->assertTrue($response->timedOut);
    }
}
