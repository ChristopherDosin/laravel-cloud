<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\DatabaseBackup;
use App\Jobs\DeleteDatabaseBackup;
use Illuminate\Support\Facades\Bus;
use App\Events\DatabaseBackupFailed;
use App\Events\DatabaseBackupRunning;
use Illuminate\Support\Facades\Event;
use App\Events\DatabaseBackupFinished;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_mark_as_running()
    {
        Event::fake();

        $backup = factory(DatabaseBackup::class)->create();
        $backup->markAsRunning();

        $this->assertEquals('running', $backup->status);

        Event::assertDispatched(DatabaseBackupRunning::class);
    }


    public function test_mark_as_finished()
    {
        Event::fake();

        $backup = factory(DatabaseBackup::class)->create();
        $backup->markAsFinished('output');

        $this->assertEquals('finished', $backup->status);
        $this->assertEquals('output', $backup->output);

        Event::assertDispatched(DatabaseBackupFinished::class);
    }


    public function test_mark_as_failed()
    {
        Event::fake();

        $backup = factory(DatabaseBackup::class)->create();
        $backup->markAsFailed(1, 'output');

        $this->assertEquals('failed', $backup->status);
        $this->assertEquals(1, $backup->exit_code);
        $this->assertEquals('output', $backup->output);

        Event::assertDispatched(DatabaseBackupFailed::class);
    }


    public function test_deleting_a_backup_dispatches_the_delete_job()
    {
        Bus::fake();

        $backup = factory(DatabaseBackup::class)->create();
        $backup->delete();

        Bus::assertDispatched(DeleteDatabaseBackup::class, function ($job) use ($backup) {
            return $backup->storageProvider->id === $job->provider->id;
        });
    }
}
