<?php

namespace Tests\Feature;

use App\AppServer;
use Tests\TestCase;
use App\Scripts\ProvisionAppServer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProvisionAppServerScriptTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_script_can_be_rendered()
    {
        $server = factory(AppServer::class)->create();

        $script = new ProvisionAppServer($server);

        $script = $script->script();

        $this->assertNotNull($script);
    }
}
