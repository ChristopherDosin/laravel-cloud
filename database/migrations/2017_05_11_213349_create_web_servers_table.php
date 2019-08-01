<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWebServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('web_servers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('project_id')->index();
            $table->unsignedInteger('stack_id')->index();
            $table->string('name');
            $table->string('size', 25);
            $table->string('provider_server_id')->nullable();
            $table->integer('port')->default(22);
            $table->string('sudo_password');
            $table->text('meta');
            $table->timestamp('provisioning_job_dispatched_at')->nullable();
            $table->string('status', 25)->default('pending');
            $table->string('daemon_status', 25)->default('pending');
            $table->timestamps();
        });
    }
}
