<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Scripts\Build;
use App\ServerDeployment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BuildScriptTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_script_can_be_rendered()
    {
        $deployment = factory(ServerDeployment::class)->create();

        $deployment->deployable->createDaemonGeneration();

        $script = new Build($deployment);

        $this->assertNotNull($script->script());
    }
}
