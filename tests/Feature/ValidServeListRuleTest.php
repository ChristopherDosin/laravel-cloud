<?php

namespace Tests\Feature;

use App\Stack;
use App\WebServer;
use Tests\TestCase;
use App\Environment;
use App\Rules\ValidServeList;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ValidServeListRuleTest extends TestCase
{
    use RefreshDatabase;


    public function test_rule_passes_when_not_being_served_by_other_environments()
    {
        $stack = factory(Stack::class)->create();
        $rule = new ValidServeList($stack->environment->project);
        $this->assertTrue($rule->passes('web.serves', ['laravel.com']));
    }


    public function test_rule_fails_when_being_served_by_other_environments()
    {
        $stack = factory(Stack::class)->create();

        $project = $stack->environment->project;
        $project->environments()->save($environment = factory(Environment::class)->make());
        $environment->stacks()->save($otherStack = factory(Stack::class)->make());
        $otherStack->webServers()->save(factory(WebServer::class)->make([
            'meta' => ['serves' => ['laravel.com']],
        ]));

        $rule = new ValidServeList($stack->environment->project);
        $this->assertFalse($rule->passes('web.serves', ['laravel.com']));
    }
}
