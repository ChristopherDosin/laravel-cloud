<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\DatabaseBackup;
use App\DatabaseRestore;
use App\Scripts\RestoreDatabaseBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RestoreDatabaseBackupScriptTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_script_can_be_rendered()
    {
        $backup = factory(DatabaseBackup::class)->create();

        $backup->restores()->save($restore = factory(DatabaseRestore::class)->make());

        $script = new RestoreDatabaseBackup($restore);

        $this->assertNotNull($script->script());
    }
}
