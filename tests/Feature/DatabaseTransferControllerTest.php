<?php

namespace Tests\Feature;

use App\Project;
use App\Database;
use Tests\TestCase;
use App\Jobs\SyncNetwork;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatabaseTransferControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_databases_can_be_transferred_to_another_project()
    {
        Bus::fake();

        $database = factory(Database::class)->create();
        $project = $database->project;

        $anotherProject = factory(Project::class)->create([
            'user_id' => $project->user->id
        ]);

        $response = $this->actingAs(
            $database->project->user, 'api'
        )->json('post', '/api/database/'.$database->id.'/transfers', [
            'project_id' => $anotherProject->id,
        ]);

        $response->assertStatus(200);

        $this->assertFalse($project->fresh()->databases->contains($database));
        $this->assertTrue($anotherProject->fresh()->databases->contains($database));

        Bus::assertDispatched(SyncNetwork::class);
    }


    public function test_databases_cant_be_transferred_to_another_users_project()
    {
        Bus::fake();

        $database = factory(Database::class)->create();
        $project = $database->project;

        $anotherProject = factory(Project::class)->create();

        $response = $this->withExceptionHandling()->actingAs(
            $database->project->user, 'api'
        )->json('post', '/api/database/'.$database->id.'/transfers', [
            'project_id' => $anotherProject->id,
        ]);

        $response->assertStatus(422);
    }


    public function test_databases_cant_be_transferred_without_permission()
    {
        Bus::fake();

        $database = factory(Database::class)->create();
        $project = $database->project;

        $anotherProject = factory(Project::class)->create([
            'user_id' => $project->user->id
        ]);

        $project->shareWith($user = $this->user());

        $response = $this->withExceptionHandling()->actingAs(
            $user, 'api'
        )->json('post', '/api/database/'.$database->id.'/transfers', [
            'project_id' => $anotherProject->id,
        ]);

        $response->assertStatus(403);
    }
}
