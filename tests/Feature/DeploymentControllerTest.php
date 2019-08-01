<?php

namespace Tests\Feature;

use App\Stack;
use App\Deployment;
use Tests\TestCase;
use App\Jobs\MonitorDeployment;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeploymentControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_deployment_can_be_retrieved()
    {
        $deployment = factory(Deployment::class)->create();

        $response = $this->actingAs(
            $deployment->stack->environment->project->user, 'api'
        )->get('/api/deployment/'.$deployment->id);

        $response->assertStatus(200);

        $response->assertJson([
            'id' => $deployment->id,
        ]);
    }


    public function test_deployment_can_be_created()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create([
            'status' => 'provisioned',
        ]);

        $response = $this->withExceptionHandling()->actingAs(
            $stack->environment->project->user, 'api'
        )->json('post', '/api/stack/'.$stack->id.'/deployment', [
            'branch' => 'master',
            'build' => ['first', 'second'],
            'activate' => ['third', 'fourth'],
        ]);

        $stack->deploymentLock()->release();

        $response->assertStatus(201);

        $stack = $stack->fresh();
        $deployment = $stack->deployments->first();

        $this->assertEquals($stack->environment->project->user->id, $deployment->initiator->id);
        $this->assertNotNull('master', $deployment->commit_hash);
        $this->assertNotNull('pending', $deployment->status);
        $this->assertEquals(['first', 'second'], $deployment->build_commands);
        $this->assertEquals(['third', 'fourth'], $deployment->activation_commands);

        Bus::assertDispatched(MonitorDeployment::class);
    }


    public function test_deployment_can_not_be_created_with_invalid_branch()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create([
            'status' => 'provisioned',
        ]);

        $response = $this->withExceptionHandling()->actingAs(
            $stack->environment->project->user, 'api'
        )->json('post', '/api/stack/'.$stack->id.'/deployment', [
            'branch' => 'doesnt_exist',
        ]);

        $stack->deploymentLock()->release();

        $response->assertStatus(422);
    }


    public function test_deployment_can_be_created_via_hash()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create([
            'status' => 'provisioned',
        ]);

        $response = $this->actingAs(
            $stack->environment->project->user, 'api'
        )->json('post', '/api/stack/'.$stack->id.'/deployment', [
            'hash' => '3b478197c05f0bb60ee484e01389bd2fff1d2bfc',
        ]);

        $stack->deploymentLock()->release();

        $response->assertStatus(201);

        $stack = $stack->fresh();
        $deployment = $stack->deployments->first();

        $this->assertEquals('3b478197c05f0bb60ee484e01389bd2fff1d2bfc', $deployment->commit_hash);
        $this->assertNotNull('pending', $deployment->status);

        Bus::assertDispatched(MonitorDeployment::class);
    }


    public function test_deployment_fails_if_stack_is_not_provisioned()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create([
            'status' => 'provisioning',
        ]);

        $response = $this->withExceptionHandling()->actingAs(
            $stack->environment->project->user, 'api'
        )->json('post', '/api/stack/'.$stack->id.'/deployment', [
            'hash' => '3b478197c05f0bb60ee484e01389bd2fff1d2bfc',
        ]);

        $stack->deploymentLock()->release();

        $response->assertStatus(422);
    }


    public function test_deployment_fails_if_hash_is_invalid()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create([
            'status' => 'provisioned',
        ]);

        $response = $this->withExceptionHandling()->actingAs(
            $stack->environment->project->user, 'api'
        )->json('post', '/api/stack/'.$stack->id.'/deployment', [
            'hash' => 'foo',
        ]);

        $stack->deploymentLock()->release();

        $response->assertStatus(422);
    }


    public function test_deployment_can_be_cancelled()
    {
        $deployment = factory(Deployment::class)->create();

        $response = $this->actingAs(
            $deployment->stack->project()->user, 'api'
        )->json('delete', '/api/deployment/'.$deployment->id);

        $response->assertStatus(200);
    }


    public function test_latest_deployment_can_be_cancelled()
    {
        $deployment = factory(Deployment::class)->create();

        $response = $this->actingAs(
            $deployment->stack->project()->user, 'api'
        )->json('delete', '/api/stack/'.$deployment->stack->id.'/deployment');

        $response->assertStatus(200);
    }


    public function test_deployment_cant_be_cancelled_when_its_already_activating()
    {
        $deployment = factory(Deployment::class)->create([
            'status' => 'activating',
        ]);

        $response = $this->actingAs(
            $deployment->stack->project()->user, 'api'
        )->json('delete', '/api/deployment/'.$deployment->id);

        $response->assertStatus(400);
    }


    public function test_cant_cancel_deployment_for_stack_with_no_deployments()
    {
        $stack = factory(Stack::class)->create([
            'status' => 'provisioned',
        ]);

        $response = $this->actingAs(
            $stack->project()->user, 'api'
        )->json('delete', '/api/stack/'.$stack->id.'/deployment');

        $response->assertStatus(400);
    }
}
