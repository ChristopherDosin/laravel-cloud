<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Jobs\Build;
use App\ServerDeployment;
use App\ShellProcessRunner;
use App\Callbacks\CheckBuild;
use App\Scripts\Build as BuildScript;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BuildJobTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_task_id_is_stored()
    {
        $serverDeployment = factory(ServerDeployment::class)->create();
        $serverDeployment->setRelation('deployable', $deployable = new BuildJobTestFakeDeployable);

        $job = new Build($serverDeployment);

        ShellProcessRunner::mock([
            (object) ['exitCode' => 0, 'output' => '', 'timedOut' => false],
        ]);

        $job->handle();

        $this->assertEquals(123, $serverDeployment->fresh()->build_task_id);
        $this->assertInstanceOf(BuildScript::class, $deployable->script);
        $this->assertInstanceOf(CheckBuild::class, $deployable->options['then'][0]);
    }
}


class BuildJobTestFakeDeployable
{
    public $script;
    public $options;

    public function runInBackground($script, $options)
    {
        $this->script = $script;
        $this->options = $options;

        return (object) ['id' => 123];
    }
}
