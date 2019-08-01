<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Scripts\Activate;
use App\ServerDeployment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivateScriptTest extends TestCase
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

        $script = new Activate($deployment);

        $this->assertNotNull($script->script());
    }
}
