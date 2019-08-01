<?php

namespace Tests\Feature;

use App\Stack;
use App\AppServer;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ServerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_should_respond_to_returns_proper_addresses()
    {
        $server = factory(AppServer::class)->create([
            'meta' => [
                'serves' => [
                    'laravel.com',
                ],
            ],
        ]);

        $this->assertEquals([
            'laravel.com:80',
            'laravel.com:443',
            'www.laravel.com:80',
            'www.laravel.com:443',
            $server->stack->url.'.laravel.build:80',
            $server->stack->url.'.laravel.build:443',
        ], $server->shouldRespondToWithPorts());
    }


    public function test_daemon_generations_are_trimmed()
    {
        $server = factory(AppServer::class)->create();

        for ($i = 0; $i < 30; $i++) {
            $server->createDaemonGeneration();
        }

        $this->assertEquals(10, $server->daemonGenerations()->count());
    }
}
