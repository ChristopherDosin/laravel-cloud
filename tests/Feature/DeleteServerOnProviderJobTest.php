<?php

namespace Tests\Feature;

use App\Project;
use Tests\TestCase;
use App\Jobs\DeleteServerOnProvider;
use Facades\App\ServerProviderClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeleteServerOnProviderJobTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_server_is_deleted_using_provider()
    {
        $project = factory(Project::class)->create();

        $job = new DeleteServerOnProvider($project, '123');

        ServerProviderClientFactory::shouldReceive('make->deleteServerById')
                        ->with('123');

        $job->handle();

        $this->assertTrue(true);
    }
}
