<?php

use App\User;
use App\Project;
use App\ServerProvider;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = factory(User::class)->create([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'api_token' => 'laravel',
        ]);

        $provider = factory(ServerProvider::class)->create([
            'user_id' => $user->id,
        ]);

        $project = factory(Project::class)->create([
            'user_id' => $user->id,
            'server_provider_id' => $provider->id,
            'region' => 'nyc3',
        ]);
    }
}
