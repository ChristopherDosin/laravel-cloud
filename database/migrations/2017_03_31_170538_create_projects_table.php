<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->index();
            $table->string('name');
            $table->unsignedInteger('server_provider_id')->index();
            $table->string('region', 25);
            $table->unsignedInteger('source_provider_id')->index();
            $table->string('repository');
            $table->tinyInteger('archived')->default(0);
            $table->timestamps();
        });
    }
}
