<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\S3;
use App\DatabaseBackup;
use App\StorageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StorageProviderControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_storage_provider_can_be_created()
    {
        $user = $this->user();

        $response = $this->actingAs($user, 'api')->json('POST', '/api/storage-provider', [
            'name' => 'Personal',
            'type' => 'S3',
            'meta' => [
                'key' => env('S3_KEY'),
                'secret' => env('S3_SECRET'),
                'region' => 'us-east-1',
                'bucket' => 'laravel-cloud-test',
            ],
        ]);

        $response->assertStatus(201);
        $this->assertCount(1, $user->storageProviders);

        $storage = $user->storageProviders->first();
        $this->assertEquals('Personal', $storage->name);
        $this->assertEquals('S3', $storage->type);
        $this->assertInstanceOf(S3::class, $storage->client());
    }


    public function test_storage_provider_can_be_validated()
    {
        $user = $this->user();

        $response = $this->withExceptionHandling()->actingAs($user, 'api')->json('POST', '/api/storage-provider', [
            'name' => 'Personal',
            'type' => 'GitHub',
            'meta' => [],
        ]);

        $response->assertStatus(422);
        $this->assertCount(0, $user->storageProviders);
    }


    public function test_storage_provider_can_be_deleted()
    {
        $provider = factory(StorageProvider::class)->create();
        $provider->backups()->save($backup = factory(DatabaseBackup::class)->make());

        $response = $this->actingAs($provider->user, 'api')->json(
            'DELETE', "/api/storage-provider/{$provider->id}"
        );

        $response->assertStatus(200);
        $this->assertCount(0, $provider->user->storageProviders()->get());
        $this->assertCount(0, $provider->backups);
    }


    public function test_only_owners_may_delete_storage_providers()
    {
        $provider = factory(StorageProvider::class)->create();
        $provider->backups()->save($backup = factory(DatabaseBackup::class)->make());

        $response = $this->withExceptionHandling()->actingAs($this->user(), 'api')->json(
            'DELETE', "/api/storage-provider/{$provider->id}"
        );

        $response->assertStatus(403);
    }
}
