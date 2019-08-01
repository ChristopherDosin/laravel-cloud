<?php

namespace Tests\Feature;

use Mockery;
use Exception;
use App\Balancer;
use App\IpAddress;
use Tests\TestCase;
use App\Jobs\ProvisionBalancer;
use Illuminate\Support\Facades\Bus;
use App\Jobs\DeleteServerOnProvider;
use Facades\App\ServerProviderClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProvisionBalancerJobtest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_balancer_is_deleted_on_failure()
    {
        Bus::fake();

        $balancer = factory(Balancer::class)->create();
        $balancer->address()->save($address = factory(IpAddress::class)->make());

        ServerProviderClientFactory::shouldReceive('make->deleteServer')->with(Mockery::on(function ($value) use ($balancer) {
            return $value->id == $balancer->id;
        }));

        $job = new ProvisionBalancer($balancer);
        $job->failed(new Exception);

        Bus::assertDispatched(DeleteServerOnProvider::class);
        $this->assertCount(1, $balancer->project->alerts);
    }
}
