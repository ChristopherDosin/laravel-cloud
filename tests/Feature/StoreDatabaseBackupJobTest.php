<?php

namespace Tests\Feature;

use Mockery;
use App\Database;
use Tests\TestCase;
use App\DatabaseBackup;
use Tests\Fakes\FakeTask;
use Facades\App\TaskFactory;
use App\Jobs\StoreDatabaseBackup;
use App\Callbacks\CheckDatabaseBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Scripts\StoreDatabaseBackup as StoreDatabaseBackupScript;

class StoreDatabaseBackupJobTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_script_is_started()
    {
        $backup = factory(DatabaseBackup::class)->create();

        $job = new StoreDatabaseBackup($backup);

        TaskFactory::shouldReceive('createFromScript')->once()->with(
            Mockery::type(Database::class), Mockery::type(StoreDatabaseBackupScript::class), Mockery::on(function ($options) use ($backup) {
                return $options['then'][0] instanceof CheckDatabaseBackup &&
                       $options['then'][0]->id === $backup->id;
            })
        )->andReturn($task = new FakeTask);

        $job->handle();

        $this->assertTrue($task->ranInBackground);
    }
}
