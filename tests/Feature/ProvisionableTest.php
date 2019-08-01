<?php

namespace Tests\Feature;

use App\Database;
use App\IpAddress;
use Tests\TestCase;
use App\ShellProcessRunner;
use Facades\App\ServerProviderClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProvisionableTest extends TestCase
{
    use RefreshDatabase;


    public function test_basic_accessors_and_helpers()
    {
        $database = factory(Database::class)->create();
        $database->address()->save(factory(IpAddress::class)->make());

        $this->assertEquals('127.0.0.1', $database->ipAddress());
        $this->assertEquals('127.0.0.2', $database->privateIpAddress());
        $this->assertTrue(file_exists($database->ownerKeyPath()));
        $this->assertEquals(1, $database->providerServerId());

        $this->assertFalse($database->isProvisioning());
        $this->assertTrue($database->isProvisioned());

        $database->markAsProvisioning();
        $this->assertTrue($database->isProvisioning());
        $this->assertFalse($database->isProvisioned());

        $database->markAsProvisioned();
        $this->assertFalse($database->isProvisioning());
        $this->assertTrue($database->isProvisioned());
    }


    public function test_determining_if_ready_for_provisioning_will_retrieve_ip_addresses()
    {
        $database = factory(Database::class)->create();

        ServerProviderClientFactory::shouldReceive('make->getPublicIpAddress')->with($database)->andReturn('127.0.0.3');
        ServerProviderClientFactory::shouldReceive('make->getPrivateIpAddress')->with($database)->andReturn('127.0.0.4');

        ShellProcessRunner::mock([
            (object) ['exitCode' => 0, 'output' => '/root', 'timedOut' => false],
            (object) ['exitCode' => 0, 'output' => '', 'timedOut' => false],
        ]);

        $this->assertTrue($database->isReadyForProvisioning());

        $database = $database->fresh();

        $this->assertEquals('127.0.0.3', $database->ipAddress());
        $this->assertEquals('127.0.0.4', $database->privateIpAddress());
    }


    public function test_determining_if_ready_for_provisioning_will_skip_pulling_ip_addresses_if_already_present()
    {
        $database = factory(Database::class)->create();
        $database->address()->save(factory(IpAddress::class)->make());

        ServerProviderClientFactory::shouldReceive('make->getPublicIpAddress')->never();
        ServerProviderClientFactory::shouldReceive('make->getPrivateIpAddress')->never();

        ShellProcessRunner::mock([
            (object) ['exitCode' => 0, 'output' => '/root', 'timedOut' => false],
            (object) ['exitCode' => 0, 'output' => '', 'timedOut' => false],
        ]);

        $this->assertTrue($database->isReadyForProvisioning());
    }


    public function test_determining_if_ready_for_provisioning_will_return_false_if_no_output()
    {
        $database = factory(Database::class)->create();
        $database->address()->save(factory(IpAddress::class)->make());

        ServerProviderClientFactory::shouldReceive('make->getPublicIpAddress')->never();
        ServerProviderClientFactory::shouldReceive('make->getPrivateIpAddress')->never();

        ShellProcessRunner::mock([
            (object) ['exitCode' => 0, 'output' => '', 'timedOut' => false],
        ]);

        $this->assertFalse($database->isReadyForProvisioning());
    }


    public function test_determining_if_ready_for_provisioning_will_return_false_if_apt_is_locked()
    {
        $database = factory(Database::class)->create();
        $database->address()->save(factory(IpAddress::class)->make());

        ServerProviderClientFactory::shouldReceive('make->getPublicIpAddress')->never();
        ServerProviderClientFactory::shouldReceive('make->getPrivateIpAddress')->never();

        ShellProcessRunner::mock([
            (object) ['exitCode' => 0, 'output' => '/root', 'timedOut' => false],
            (object) ['exitCode' => 0, 'output' => 'something', 'timedOut' => false],
        ]);

        $this->assertFalse($database->isReadyForProvisioning());
    }
}
