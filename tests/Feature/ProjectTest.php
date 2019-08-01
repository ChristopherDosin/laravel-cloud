<?php

namespace Tests\Feature;

use App\User;
use App\Project;
use Tests\TestCase;
use App\Events\ProjectShared;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;


    public function test_can_determine_if_user_has_access_to_project()
    {
        $user = $this->user();
        $anotherUser = $this->user();

        $anotherUser->projects()->save($project = factory(Project::class)->create());
        $this->assertFalse($user->canAccessProject($project));
        $this->assertTrue($anotherUser->canAccessProject($project));

        $project->shareWith($user, ['ssh:server']);

        $this->assertTrue($user->fresh()->canAccessProject($project));
        $this->assertTrue($anotherUser->fresh()->canAccessProject($project));

        $project->stopSharingWith($user);

        $this->assertFalse($user->fresh()->canAccessProject($project));
        $this->assertTrue($anotherUser->fresh()->canAccessProject($project));
    }


    public function test_proper_share_events_are_fired()
    {
        Event::fake();

        $user = $this->user();
        $anotherUser = $this->user();

        $anotherUser->projects()->save($project = factory(Project::class)->create());

        $project->shareWith($user);

        Event::assertDispatched(ProjectShared::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }


    public function test_project_can_only_have_30_alerts()
    {
        $project = factory(Project::class)->create();

        for ($i = 0; $i < 40; $i++) {
            $project->alerts()->create([
                'type' => 'Test',
                'exception' => '',
                'meta' => [],
            ]);
        }

        $this->assertEquals(30, $project->alerts()->count());
    }
}
