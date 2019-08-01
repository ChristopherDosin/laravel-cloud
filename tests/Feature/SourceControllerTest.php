<?php

namespace Tests\Feature;

use App\Project;
use Tests\TestCase;
use App\SourceProvider;
use App\Services\GitHub;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SourceControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_source_can_be_created()
    {
        $user = $this->user();

        $response = $this->actingAs($user, 'api')->json('POST', '/api/source-provider', [
            'name' => 'Personal',
            'type' => 'GitHub',
            'meta' => ['token' => env('GITHUB_TEST_KEY')],
        ]);

        $response->assertStatus(201);
        $this->assertCount(1, $user->sourceProviders);

        $source = $user->sourceProviders->first();
        $this->assertEquals('Personal', $source->name);
        $this->assertEquals('GitHub', $source->type);
        $this->assertEquals(env('GITHUB_TEST_KEY'), $source->meta['token']);
        $this->assertInstanceOf(GitHub::class, $source->client());
    }


    public function test_source_can_be_validated()
    {
        $user = $this->user();

        $response = $this->withExceptionHandling()->actingAs($user, 'api')->json('POST', '/api/source-provider', [
            'name' => 'Personal',
            'type' => 'GitHub',
            'meta' => ['token' => 'foo'],
        ]);

        $response->assertStatus(422);
        $this->assertCount(0, $user->sourceProviders);
    }


    public function test_source_provider_can_be_deleted()
    {
        $provider = factory(SourceProvider::class)->create();

        $response = $this->actingAs($provider->user, 'api')->json(
            'DELETE', "/api/source-provider/{$provider->id}"
        );

        $response->assertStatus(200);
        $this->assertCount(0, $provider->user->sourceProviders()->get());
    }


    public function test_only_owner_can_delete_source_providers()
    {
        $provider = factory(SourceProvider::class)->create();

        $response = $this->withExceptionHandling()->actingAs($this->user(), 'api')->json(
            'DELETE', "/api/source-provider/{$provider->id}"
        );

        $response->assertStatus(403);
    }


    public function test_source_providers_can_not_be_deleted_if_attached_to_projects()
    {
        $provider = factory(SourceProvider::class)->create();
        $provider->projects()->save($project = factory(Project::class)->make());

        $response = $this->withExceptionHandling()->actingAs($provider->user, 'api')->json(
            'DELETE', "/api/source-provider/{$provider->id}"
        );

        $response->assertStatus(422);
    }
}
