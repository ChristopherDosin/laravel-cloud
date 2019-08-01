<?php

namespace Tests\Feature;

use App\Project;
use Tests\TestCase;
use App\SourceProvider;
use App\Rules\ValidSourceName;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ValidSourceNameRuleTest extends TestCase
{
    use RefreshDatabase;


    public function test_rule_passes_when_source_exists()
    {
        $source = factory(SourceProvider::class)->create();
        $source->user->projects()->save($project = factory(Project::class)->make());
        $rule = new ValidSourceName($project);
        $this->assertTrue($rule->passes('source', $source->name));
    }


    public function test_rule_fails_when_source_doesnt_exist()
    {
        $source = factory(SourceProvider::class)->create();
        $source->user->projects()->save($project = factory(Project::class)->make());
        $rule = new ValidSourceName($project);
        $this->assertFalse($rule->passes('source', 'missing'));
    }


    public function test_rule_passes_when_not_a_project()
    {
        $rule = new ValidSourceName('something');
        $this->assertTrue($rule->passes('source', 'missing'));
    }
}
