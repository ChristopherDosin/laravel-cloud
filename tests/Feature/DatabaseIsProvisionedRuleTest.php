<?php

namespace Tests\Feature;

use App\Project;
use App\Database;
use Tests\TestCase;
use App\Rules\DatabaseIsProvisioned;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatabaseIsProvisionedRuleTest extends TestCase
{
    use RefreshDatabase;


    public function test_rule_passes_when_database_exists()
    {
        $database = factory(Database::class)->create();
        $rule = new DatabaseIsProvisioned($database->project);
        $this->assertTrue($rule->passes('database', $database->name));
    }


    public function test_rule_passes_when_database_doesnt_exist()
    {
        // Let this pass because valid name will catch this case...
        $database = factory(Database::class)->create();
        $rule = new DatabaseIsProvisioned($database->project);
        $this->assertTrue($rule->passes('database', 'missing'));
    }


    public function test_rule_fails_when_database_is_not_provisioned()
    {
        $database = factory(Database::class)->create(['status' => 'pending']);
        $rule = new DatabaseIsProvisioned($database->project);
        $this->assertFalse($rule->passes('database', $database->name));
    }
}
