<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDatabasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('databases', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('project_id')->index();
            $table->string('name');
            $table->string('size', 25);
            $table->string('provider_server_id')->nullable();
            $table->integer('port')->default(22);
            $table->string('sudo_password');
            $table->string('username');
            $table->string('password');
            $table->text('allows_access_from');
            $table->timestamp('provisioning_job_dispatched_at')->nullable();
            $table->string('status', 25);
            $table->timestamps();

            $table->unique(['project_id', 'name']);
        });
    }
}
