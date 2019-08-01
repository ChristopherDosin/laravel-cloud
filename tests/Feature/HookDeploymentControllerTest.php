<?php

namespace Tests\Feature;

use App\Hook;
use App\Stack;
use App\Deployment;
use Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HookDeploymentControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_deployment_can_be_created_from_hook_payload()
    {
        Bus::fake();

        $hook = factory(Hook::class)->create();

        $hook->stack->deploymentLock()->release();

        $hook->stack->environment->update([
            'name' => 'workbench',
        ]);

        $hook->stack->update([
            'name' => 'stack-1',
        ]);

        $response = $this->json('post', '/api/hook-deployment/'.$hook->id.'/'.$hook->token, [
            'ref' => 'refs/heads/'.$hook->branch,
            'head_commit' => [
                'id' => 'd8f05f1696032982dd8bf77aa9186d2aea744801',
            ],
            'repository' => [
                'full_name' => 'taylorotwell/hello-world',
            ],
        ]);

        $response->assertStatus(201);
        $this->assertInstanceOf(Deployment::class, $response->original);

        $this->assertNull($response->original->branch);
        $this->assertEquals('d8f05f1696032982dd8bf77aa9186d2aea744801', $response->original->commit_hash);
    }


    public function test_deployment_can_be_created_from_codeship_payloads()
    {
        Bus::fake();

        $hook = factory(Hook::class)->create(['published' => false]);

        $hook->stack->deploymentLock()->release();

        $hook->stack->environment->update([
            'name' => 'workbench',
        ]);

        $hook->stack->update([
            'name' => 'stack-1',
        ]);

        $response = $this->withHeaders([
            'User-Agent' => 'Codeship Webhook',
        ])->json('post', '/api/hook-deployment/'.$hook->id.'/'.$hook->token, [
            'build' => [
                'status' => 'success',
                'commit_id' => 'd8f05f1696032982dd8bf77aa9186d2aea744801',
            ],
        ]);

        $response->assertStatus(201);
        $this->assertInstanceOf(Deployment::class, $response->original);

        $this->assertNull($response->original->branch);
        $this->assertEquals('d8f05f1696032982dd8bf77aa9186d2aea744801', $response->original->commit_hash);
    }


    public function test_deployment_can_be_created_for_latest_commit()
    {
        Bus::fake();

        $hook = factory(Hook::class)->create();

        $hook->stack->deploymentLock()->release();

        $hook->stack->environment->update([
            'name' => 'workbench',
        ]);

        $hook->stack->update([
            'name' => 'stack-1',
        ]);

        $response = $this->json('post', '/api/hook-deployment/'.$hook->id.'/'.$hook->token, [
            'ref' => 'refs/heads/'.$hook->branch,
            'repository' => [
                'full_name' => 'taylorotwell/hello-world',
            ],
        ]);

        $response->assertStatus(201);
        $this->assertInstanceOf(Deployment::class, $response->original);

        $this->assertNull($response->original->branch);

        $latestCommit = $hook->sourceProvider()->client()->latestHashFor('taylorotwell/hello-world', 'master');
        $this->assertEquals($latestCommit, $response->original->commit_hash);
    }


    public function test_request_token_must_match_hook_token()
    {
        Bus::fake();

        $hook = factory(Hook::class)->create();

        $hook->stack->deploymentLock()->release();

        $response = $this->withExceptionHandling()->json('post', '/api/hook-deployment/'.$hook->id.'/token', [
            'ref' => 'refs/heads/'.$hook->branch,
            'head_commit' => [
                'id' => '3b478197c05f0bb60ee484e01389bd2fff1d2bfc',
            ],
            'repository' => [
                'full_name' => 'taylorotwell/hello-world',
            ],
        ]);

        $response->assertStatus(403);
    }


    public function test_branch_must_be_received_by_hook()
    {
        Bus::fake();

        $hook = factory(Hook::class)->create(['published' => true]);

        $hook->stack->deploymentLock()->release();

        $response = $this->withExceptionHandling()->json('post', '/api/hook-deployment/'.$hook->id.'/'.$hook->token, [
            'ref' => 'refs/heads/something',
            'head_commit' => [
                'id' => '3b478197c05f0bb60ee484e01389bd2fff1d2bfc',
            ],
            'repository' => [
                'full_name' => 'taylorotwell/hello-world',
            ],
        ]);

        $response->assertStatus(204);
        $this->assertNull($response->original);
    }


    public function test_irrelevant_codeship_hooks_are_discarded()
    {
        Bus::fake();

        $hook = factory(Hook::class)->create(['published' => false]);

        $hook->stack->deploymentLock()->release();

        $response = $this->withExceptionHandling()->withHeaders([
            'User-Agent' => 'Codeship Webhook',
        ])->json('post', '/api/hook-deployment/'.$hook->id.'/'.$hook->token, [
            'build' => [
                'status' => 'failed',
                'commit_id' => 'something',
            ],
        ]);

        $response->assertStatus(204);
        $this->assertNull($response->original);
    }


    public function test_hook_always_receives_commits_if_it_is_not_published()
    {
        Bus::fake();

        $hook = factory(Hook::class)->create(['published' => false]);

        $hook->stack->deploymentLock()->release();

        $hook->stack->environment->update([
            'name' => 'workbench',
        ]);

        $hook->stack->update([
            'name' => 'stack-1',
        ]);

        $response = $this->json('post', '/api/hook-deployment/'.$hook->id.'/'.$hook->token, [
            'ref' => 'refs/heads/something',
            'repository' => [
                'full_name' => 'taylorotwell/hello-world',
            ],
        ]);

        $response->assertStatus(201);
    }


    public function test_404_is_returned_if_manifest_is_not_found()
    {
        Bus::fake();

        $hook = factory(Hook::class)->create();

        $hook->stack->deploymentLock()->release();

        $response = $this->withExceptionHandling()->json('post', '/api/hook-deployment/'.$hook->id.'/'.$hook->token, [
            'ref' => 'refs/heads/'.$hook->branch,
            'head_commit' => [
                'id' => '3b478197c05f0bb60ee484e01389bd2fff1d2bfc',
            ],
            'repository' => [
                'full_name' => 'taylorotwell/hello-world',
            ],
        ]);

        $response->assertStatus(404);
    }
}
