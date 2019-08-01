<?php

namespace App\Providers;

use App\Services\Route53;
use App\Contracts\DnsProvider;
use App\Contracts\YamlParser;
use Aws\Route53\Route53Client;
use App\Services\LocalYamlParser;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(YamlParser::class, LocalYamlParser::class);
        $this->app->bind(DnsProvider::class, Route53::class);

        $this->app->bind(Route53Client::class, function () {
            return new Route53Client([
                'version' => 'latest',
                'region' => 'us-east-1',
                'credentials' => [
                    'key' => config('services.route53.key'),
                    'secret' => config('services.route53.secret'),
                ],
            ]);
        });
    }
}
