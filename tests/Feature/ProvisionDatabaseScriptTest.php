<?php

namespace Tests\Feature;

use App\Database;
use Tests\TestCase;
use App\Scripts\ProvisionDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProvisionDatabaseScriptTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_script_can_be_rendered()
    {
        $database = factory(Database::class)->create();
        $script = new ProvisionDatabase($database);
        $script = $script->script();

        $this->assertNotNull($script);
    }
}
