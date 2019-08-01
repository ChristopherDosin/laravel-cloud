<?php

namespace Tests\Feature;

use App\Project;
use App\Database;
use Tests\TestCase;
use App\Rules\ValidDatabaseName;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ValidDatabaseNameRuleTest extends TestCase
{
    use RefreshDatabase;


    public function test_rule_passes_when_database_exists()
    {
        $database = factory(Database::class)->create();
        $rule = new ValidDatabaseName($database->project);
        $this->assertTrue($rule->passes('database', $database->name));
    }


    public function test_rule_fails_when_database_doesnt_exist()
    {
        $database = factory(Database::class)->create();
        $rule = new ValidDatabaseName($database->project);
        $this->assertFalse($rule->passes('database', 'missing'));
    }


    public function test_rule_passes_when_not_a_project()
    {
        $rule = new ValidDatabaseName('something');
        $this->assertTrue($rule->passes('database', 'missing'));
    }
}
