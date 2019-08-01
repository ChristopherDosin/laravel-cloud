<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\ServerProvider;
use App\Services\DigitalOcean;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DigitalOceanProviderTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_ssh_keys_can_be_added_and_removed()
    {
        $provider = factory(ServerProvider::class)->create();
        $this->refreshKeys($provider->user);
        $ocean = new DigitalOcean($provider);

        $this->assertNull($ocean->findKey());

        $id = $ocean->addKey();

        $this->assertNotNull($id);
        $this->assertNotNull($ocean->findKey());
        $this->assertEquals($id, $ocean->keyId());

        $ocean->removeKey();

        $this->assertNull($ocean->findKey());
    }


    public function test_can_verify_credentials_are_valid()
    {
        $provider = factory(ServerProvider::class)->create();
        $this->refreshKeys($provider->user);
        $ocean = new DigitalOcean($provider);

        $this->assertTrue($ocean->valid());

        $provider = factory(ServerProvider::class)->create(['meta' => ['token' => 'foo']]);
        $ocean = new DigitalOcean($provider);

        $this->assertFalse($ocean->valid());
    }
}
