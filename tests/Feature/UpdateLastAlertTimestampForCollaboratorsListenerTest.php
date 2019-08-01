<?php

namespace Tests\Feature;

use App\User;
use App\Project;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateLastAlertTimestampForCollaboratorsListenerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_last_alert_received_at_timestamps_are_updated()
    {
        $project = factory(Project::class)->create();
        $project->shareWith($collaborator = factory(User::class)->create());

        $this->assertNull($project->user->fresh()->last_alert_received_at);
        $this->assertNull($collaborator->fresh()->last_alert_received_at);

        $alert = $project->alerts()->create([
            'type' => 'Something',
            'exception' => 'exception',
            'meta' => [],
        ]);

        $this->assertNotNull($project->user->fresh()->last_alert_received_at);
        $this->assertNotNull($collaborator->fresh()->last_alert_received_at);
    }
}
