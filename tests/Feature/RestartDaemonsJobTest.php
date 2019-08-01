<?php

namespace Tests\Feature;

use App\Deployment;
use Tests\TestCase;
use App\ServerDeployment;
use App\ShellProcessRunner;
use App\Jobs\RestartDaemons;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RestartDaemonsJobTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_daemons_are_restarted()
    {
        $deployment = factory(Deployment::class)->create([
            'daemons' => [
                'first' => [
                    'command' => 'php artisan horizon',
                ],
            ],
        ]);

        $deployment->serverDeployments()->save(
            $serverDeployment = factory(ServerDeployment::class)->make()
        );

        ShellProcessRunner::mock([
            (object) ['exitCode' => 0, 'output' => '', 'timedOut' => false],
        ]);

        $job = new RestartDaemons($serverDeployment);
        $job->handle();

        $this->assertCount(1, $serverDeployment->deployable->fresh()->daemonGenerations);
    }
}
