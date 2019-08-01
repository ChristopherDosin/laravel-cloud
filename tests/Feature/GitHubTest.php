<?php

namespace Tests\Feature;

use App\Hook;
use App\Stack;
use App\Deployment;
use Tests\TestCase;
use App\Environment;
use App\SourceProvider;
use App\Services\GitHub;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GitHubTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_can_determine_if_credentials_are_valid()
    {
        $source = factory(SourceProvider::class)->create();
        $github = new GitHub($source);

        $this->assertTrue($github->valid());

        $source = factory(SourceProvider::class)->create(['meta' => ['token' => 'foo']]);
        $github = new GitHub($source);

        $this->assertFalse($github->valid());
    }


    public function test_can_determine_if_repository_is_valid()
    {
        $source = factory(SourceProvider::class)->create();

        $this->assertTrue($source->client()->validRepository('laravel/laravel', 'master'));
        $this->assertFalse($source->client()->validRepository('laravel/laravel', 'fake-branch-that-doesnt-exist'));
        $this->assertFalse($source->client()->validRepository('doesnt/exist', 'master'));
    }


    public function test_can_retrieve_latest_commit_hash()
    {
        $source = factory(SourceProvider::class)->create();
        $hash = $source->client()->latestHashFor('laravel/laravel', 'master');

        $this->assertNotNull($hash);
    }


    public function test_can_get_deployment_url()
    {
        $source = factory(SourceProvider::class)->create();
        $url = $source->client()->tarballUrl($deployment = factory(Deployment::class)->create());

        $this->assertNotNull($url);
    }


    public function test_hooks_can_be_published()
    {
        $hook = factory(Hook::class)->create([]);

        $source = factory(SourceProvider::class)->create();

        $source->client()->publishHook($hook);

        $this->assertNotNull($hook->meta['provider_hook_id']);

        $source->client()->unpublishHook($hook);
    }


    public function test_hooks_can_be_added_twice_without_errors()
    {
        $hook = factory(Hook::class)->create([]);

        $source = factory(SourceProvider::class)->create();

        $source->client()->publishHook($hook);
        $source->client()->publishHook($hook);
        $source->client()->unpublishHook($hook);

        $this->assertTrue(true);
    }


    public function test_manifest_can_be_retrieved()
    {
        $source = factory(SourceProvider::class)->create();

        $stack = factory(Stack::class)->create(['name' => 'stack-1']);
        $stack->environment->update(['name' => 'workbench']);

        $manifest = $source->client()->manifest(
            $stack,
            'taylorotwell/hello-world',
            'd8f05f1696032982dd8bf77aa9186d2aea744801'
        );

        $this->assertNotNull($manifest);
        $manifest = Yaml::parse($manifest);
        $this->assertEquals('Personal', $manifest['source']);
    }
}
