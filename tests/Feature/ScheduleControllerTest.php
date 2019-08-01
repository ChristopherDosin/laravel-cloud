<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Jobs\PruneTasks;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScheduleControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_prune_tasks_can_is_dispatched()
    {
        Bus::fake();

        $response = $this->post('/schedule/prune-tasks');

        $response->assertStatus(200);
        Bus::assertDispatched(PruneTasks::class);
    }
}
