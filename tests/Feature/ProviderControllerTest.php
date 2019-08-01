<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\DigitalOcean;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProviderControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_provider_can_be_created()
    {
        $user = $this->user();

        $response = $this->actingAs($user, 'api')->json('POST', '/api/server-provider', [
            'name' => 'Personal',
            'type' => 'DigitalOcean',
            'meta' => ['token' => env('DIGITAL_OCEAN_TEST_KEY')],
        ]);

        $response->assertStatus(201);
        $this->assertCount(1, $user->serverProviders);

        $provider = $user->serverProviders->first();
        $this->assertEquals('Personal', $provider->name);
        $this->assertEquals('DigitalOcean', $provider->type);
        $this->assertEquals(env('DIGITAL_OCEAN_TEST_KEY'), $provider->meta['token']);
        $this->assertInstanceOf(DigitalOcean::class, $provider->client());
    }


    public function test_provider_can_be_validated()
    {
        $user = $this->user();

        $response = $this->withExceptionHandling()->actingAs($user, 'api')->json('POST', '/api/server-provider', [
            'name' => 'Personal',
            'type' => 'DigitalOcean',
            'meta' => ['token' => 'foo'],
        ]);

        $response->assertStatus(422);
        $this->assertCount(0, $user->serverProviders);
    }
}
