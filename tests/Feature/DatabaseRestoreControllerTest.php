<?php

namespace Tests\Feature;

use App\Database;
use Tests\TestCase;
use App\DatabaseBackup;
use App\DatabaseRestore;
use Illuminate\Support\Facades\Bus;
use App\Jobs\RestoreDatabaseBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatabaseRestoreControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_restores_can_be_listed()
    {
        $backup = factory(DatabaseBackup::class)->create();
        $backup->restores()->save($restore = factory(DatabaseRestore::class)->make([
            'database_id' => $backup->database->id,
        ]));

        $response = $this->actingAs($restore->database->project->user, 'api')->json(
            'GET', "/api/database/{$backup->database->id}/restores"
        );

        $response->assertStatus(200);
        $this->assertEquals($restore->id, $response->original['Test Database'][0]['id']);
        $this->assertInstanceOf(DatabaseRestore::class, $response->original['Test Database'][0]);

        // Filter by database...
        $backup = factory(DatabaseBackup::class)->create();
        $backup->restores()->save($restore = factory(DatabaseRestore::class)->make([
            'database_id' => $backup->database->id,
        ]));

        $response = $this->actingAs($restore->database->project->user, 'api')->json(
            'GET', "/api/database/{$backup->database->id}/restores?database_name=Test+Database"
        );

        $response->assertStatus(200);
        $this->assertEquals($restore->id, $response->original['Test Database'][0]['id']);
        $this->assertInstanceOf(DatabaseRestore::class, $response->original['Test Database'][0]);

        // Filter that doesn't exist...
        $backup = factory(DatabaseBackup::class)->create();
        $backup->restores()->save($restore = factory(DatabaseRestore::class)->make([
            'database_id' => $backup->database->id,
        ]));

        $response = $this->actingAs($restore->database->project->user, 'api')->json(
            'GET', "/api/database/{$backup->database->id}/restores?database_name=Missing"
        );

        $response->assertStatus(200);
        $this->assertCount(0, $response->original);
    }


    public function test_restores_can_be_created()
    {
        Bus::fake();

        $backup = factory(DatabaseBackup::class)->create();

        $response = $this->actingAs($backup->database->project->user, 'api')->json('POST', "/api/backup/{$backup->id}/restore", [
            'database_backup_id' => $backup->id,
        ]);

        $response->assertStatus(201);
        $this->assertCount(1, $backup->restores);
        $this->assertEquals('Test Database', $backup->restores[0]->database_name);

        Bus::assertDispatched(RestoreDatabaseBackup::class, function ($job) use ($response) {
            return $job->restore->id === $response->original->id;
        });
    }


    public function test_only_project_owners_can_restore_databases()
    {
        Bus::fake();

        $backup = factory(DatabaseBackup::class)->create();
        $user = $this->user();
        $backup->database->project->shareWith($user);

        $response = $this->withExceptionHandling()->actingAs($user, 'api')->json('POST', "/api/backup/{$backup->id}/restore", [
            'database_backup_id' => $backup->id,
        ]);

        $response->assertStatus(403);
    }


    public function test_restores_cant_be_created_if_database_is_not_provisioned()
    {
        Bus::fake();

        $backup = factory(DatabaseBackup::class)->create();
        $backup->database->update([
            'status' => 'pending',
        ]);

        $response = $this->withExceptionHandling()->actingAs($backup->database->project->user, 'api')->json('POST', "/api/backup/{$backup->id}/restore", [
            'database_backup_id' => $backup->id,
        ]);

        $response->assertStatus(422);

        Bus::assertNotDispatched(RestoreDatabaseBackup::class);
    }
}
