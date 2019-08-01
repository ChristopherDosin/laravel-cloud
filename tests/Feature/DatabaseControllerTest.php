<?php

namespace Tests\Feature;

use App\Project;
use App\Database;
use App\IpAddress;
use Tests\TestCase;
use App\Jobs\ProvisionDatabase;
use Illuminate\Support\Facades\Bus;
use App\Jobs\DeleteServerOnProvider;
use Facades\App\ServerProviderClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatabaseControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_databases_can_be_listed()
    {
        $database = factory(Database::class)->create();

        $response = $this->actingAs($database->project->user, 'api')
                    ->get('/api/project/'.$database->project->id.'/databases');

        $response->assertStatus(200);
        $this->assertCount(1, $response->original);
        $this->assertEquals($database->id, $response->original[0]->id);
    }


    public function test_duplicate_database_names_cant_be_created()
    {
        $database = factory(Database::class)->create(['name' => 'mysql']);

        $response = $this->withExceptionHandling()
                ->actingAs($database->project->user, 'api')
                ->json('POST', '/api/project/'.$database->project->id.'/database', [
                    'name' => 'mysql',
                    'size' => '2GB',
                ]);

        $response->assertStatus(422);
    }


    public function test_databases_can_be_created()
    {
        Bus::fake();

        $project = factory(Project::class)->create();

        ServerProviderClientFactory::shouldReceive('make->sizes')->andReturn([
            '2GB' => [],
        ]);

        ServerProviderClientFactory::shouldReceive('make->createServer')
                        ->with('mysql', '2GB', 'nyc3')
                        ->andReturn('123');

        $response = $this->actingAs($project->user, 'api')->json('POST', '/api/project/'.$project->id.'/database', [
            'name' => 'mysql',
            'size' => '2GB',
        ]);

        Bus::assertDispatched(ProvisionDatabase::class);

        $this->assertCount(1, $project->databases);
        $this->assertEquals('mysql', $project->databases[0]->name);
        $this->assertEquals('2GB', $project->databases[0]->size);
    }


    public function test_databases_can_be_deleted()
    {
        Bus::fake();

        $database = factory(Database::class)->create();
        $database->address()->save($address = factory(IpAddress::class)->make());

        $response = $this->actingAs($database->project->user, 'api')
                    ->delete('/api/database/'.$database->id);

        $response->assertStatus(200);
        $this->assertNull(Database::find($database->id));
        $this->assertNull(IpAddress::where('public_address', $address->public_address)->first());

        Bus::assertDispatched(DeleteServerOnProvider::class, function ($job) use ($database) {
            return $job->project->id == $database->project->id &&
                   $job->providerServerId == $database->providerServerId();
        });
    }


    public function test_only_project_owners_can_delete_databases()
    {
        Bus::fake();

        $database = factory(Database::class)->create();
        $database->address()->save($address = factory(IpAddress::class)->make());

        $response = $this->withExceptionHandling()->actingAs($this->user(), 'api')
                    ->delete('/api/database/'.$database->id);

        $response->assertStatus(403);
        Bus::assertNotDispatched(DeleteServerOnProvider::class);
    }
}
