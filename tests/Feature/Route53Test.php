<?php

namespace Tests\Feature;

use App\Stack;
use App\Balancer;
use App\IpAddress;
use Tests\TestCase;
use App\Services\Route53;
use Illuminate\Foundation\Testing\RefreshDatabase;

class Route53Test extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_dns_records_can_be_added()
    {
        $route53 = app(Route53::class);
        $stack = factory(Stack::class)->create(['balanced' => true]);
        $stack->environment->project->balancers()->save($balancer = factory(Balancer::class)->make());
        $balancer->address()->save(factory(IpAddress::class)->make());

        $route53->addRecord($stack);

        $stack = $stack->fresh();

        $this->assertNotNull($stack->dns_record_id);
        $this->assertEquals($balancer->address->public_address, $stack->dns_address);

        // Test re-adding a record works...
        $oldRecordId = $stack->dns_record_id;

        $route53->addRecord($stack);
        $this->assertNotEquals($oldRecordId, $stack->fresh()->dns_record_id);

        // Test deleting the record...
        $route53->deleteRecord($stack);

        $stack = $stack->fresh();

        $this->assertNull($stack->dns_record_id);
        $this->assertNull($stack->dns_address);
    }
}
