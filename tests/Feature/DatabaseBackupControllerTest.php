<?php

namespace Tests\Feature;

use App\Database;
use Tests\TestCase;
use App\DatabaseBackup;
use App\StorageProvider;
use App\Jobs\StoreDatabaseBackup;
use App\Jobs\DeleteDatabaseBackup;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatabaseBackupControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_backups_can_be_listed()
    {
        $backup = factory(DatabaseBackup::class)->create();

        $response = $this->actingAs($backup->database->project->user, 'api')->json(
            'GET', "/api/database/{$backup->database->id}/backups"
        );

        $response->assertStatus(200);
        $this->assertEquals($backup->id, $response->original['Test Database'][0]['id']);


        // Filter by database...
        $response = $this->actingAs($backup->database->project->user, 'api')->json(
            'GET', "/api/database/{$backup->database->id}/backups?database_name=Test+Database"
        );

        $response->assertStatus(200);
        $this->assertEquals($backup->id, $response->original['Test Database'][0]['id']);


        // Filter that doesn't exist...
        $response = $this->actingAs($backup->database->project->user, 'api')->json(
            'GET', "/api/database/{$backup->database->id}/backups?database_name=doesnt-exist"
        );

        $response->assertStatus(200);
        $this->assertCount(0, $response->original);
    }


    public function test_backup_can_be_created()
    {
        Bus::fake();

        $database = factory(Database::class)->create();
        $database->project->user->storageProviders()->save(
            $provider = factory(StorageProvider::class)->make()
        );

        $response = $this->actingAs($database->project->user, 'api')->json('POST', "/api/database/{$database->id}/backup", [
            'storage_provider_id' => $provider->id,
            'database_name' => 'cloud',
        ]);

        $response->assertStatus(201);
        $this->assertCount(1, $database->backups);
        $this->assertEquals('cloud', $database->backups[0]->database_name);
        $this->assertEquals($provider->id, $database->backups[0]->storage_provider_id);

        Bus::assertDispatched(StoreDatabaseBackup::class, function ($job) use ($response) {
            return $job->backup->id === $response->original->id;
        });
    }


    public function test_backup_cant_be_started_if_database_is_not_finished_provisioning()
    {
        Bus::fake();

        $database = factory(Database::class)->create([
            'status' => 'provisioning',
        ]);
        $database->project->user->storageProviders()->save(
            $provider = factory(StorageProvider::class)->make()
        );

        $response = $this->withExceptionHandling()->actingAs($database->project->user, 'api')
                    ->json('POST', "/api/database/{$database->id}/backup", [
                        'storage_provider_id' => $provider->id,
                        'database_name' => 'cloud',
                    ]);

        $response->assertStatus(422);
        $this->assertCount(0, $database->backups);

        Bus::assertNotDispatched(StoreDatabaseBackup::class);
    }


    public function test_collaborators_can_manually_start_database_backups()
    {
        Bus::fake();

        $database = factory(Database::class)->create();

        $database->project->user->storageProviders()->save(
            $provider = factory(StorageProvider::class)->make()
        );

        $user = $this->user();
        $user->storageProviders()->save($otherProvider = factory(StorageProvider::class)->make());

        $database->project->shareWith($user);

        $response = $this->withExceptionHandling()->actingAs($user, 'api')
                    ->json('POST', "/api/database/{$database->id}/backup", [
                        'storage_provider_id' => $otherProvider->id,
                        'database_name' => 'cloud',
                    ]);

        $response->assertStatus(201);
    }


    public function test_backups_can_be_deleted()
    {
        Bus::fake();

        $backup = factory(DatabaseBackup::class)->create();

        $response = $this->actingAs($backup->database->project->user, 'api')->json(
            'DELETE', "/api/backup/{$backup->id}"
        );

        $response->assertStatus(200);

        Bus::assertDispatched(DeleteDatabaseBackup::class);
    }


    public function test_only_project_owners_can_delete_backups()
    {
        Bus::fake();

        $backup = factory(DatabaseBackup::class)->create();

        $user = $this->user();
        $backup->database->project->shareWith($user);

        $response = $this->withExceptionHandling()->actingAs($user, 'api')->json(
            'DELETE', "/api/backup/{$backup->id}"
        );

        $response->assertStatus(403);

        Bus::assertNotDispatched(DeleteDatabaseBackup::class);
    }
}
