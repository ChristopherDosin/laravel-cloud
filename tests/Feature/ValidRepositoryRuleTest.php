<?php

namespace Tests\Feature;

use App\Project;
use Tests\TestCase;
use App\SourceProvider;
use App\Rules\ValidRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ValidRepositoryRuleTest extends TestCase
{
    use RefreshDatabase;


    public function test_rule_passes_when_repository_exists()
    {
        $source = factory(SourceProvider::class)->create();
        $source->user->projects()->save($project = factory(Project::class)->make());
        $rule = new ValidRepository($source, 'master');
        $this->assertTrue($rule->passes('repository', 'laravel/laravel'));
    }


    public function test_rule_fails_when_repository_doesnt_exist()
    {
        $source = factory(SourceProvider::class)->create();
        $source->user->projects()->save($project = factory(Project::class)->make());
        $rule = new ValidRepository($source, 'master');
        $this->assertFalse($rule->passes('repository', 'something/missing'));
    }


    public function test_rule_fails_when_branch_doesnt_exist()
    {
        $source = factory(SourceProvider::class)->create();
        $source->user->projects()->save($project = factory(Project::class)->make());
        $rule = new ValidRepository($source, 'missing-branch-name-x1111');
        $this->assertFalse($rule->passes('repository', 'laravel/laravel'));
    }


    public function test_rule_fails_when_no_source_given()
    {
        $rule = new ValidRepository('something', 'master');
        $this->assertFalse($rule->passes('repository', 'laravel/laravel'));
    }
}
