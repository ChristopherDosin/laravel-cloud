<?php

namespace Tests\Feature;

use Mockery;
use App\Stack;
use App\Project;
use Tests\TestCase;
use App\Environment;
use App\Contracts\DnsProvider;
use App\Jobs\UpdateStackDnsRecords;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateStackDnsRecordsJobTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_proper_stacks_are_updated()
    {
        $environment = factory(Environment::class)->create();

        $environment->stacks()->save($stack1 = factory(Stack::class)->make([
            'dns_address' => '192.168.1.1'
        ]));

        $environment->stacks()->save($stack2 = factory(Stack::class)->make([
            'dns_address' => '192.168.2.2',
        ]));

        $job = new UpdateStackDnsRecords($environment->project, '192.168.1.1');

        $dns = Mockery::mock(DnsProvider::class);

        $dns->shouldReceive('addRecord')->once()->with(Mockery::on(function ($stack) use ($stack1) {
            return $stack->id === $stack1->id;
        }));

        $dns->shouldReceive('addRecord')->never()->with(Mockery::on(function ($stack) use ($stack2) {
            return $stack->id === $stack2->id;
        }));

        $job->handle($dns);

        $this->assertTrue(true);
    }
}
