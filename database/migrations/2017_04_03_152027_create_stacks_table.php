<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stacks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('environment_id')->index();
            $table->unsignedInteger('creator_id');
            $table->string('name');
            $table->string('url')->unique();
            $table->string('dns_record_id')->nullable();
            $table->string('dns_address')->nullable();
            $table->integer('initial_server_count')->nullable();
            $table->tinyInteger('balanced')->default(0);
            $table->unsignedInteger('certificate_id')->nullable();
            $table->tinyInteger('promoted')->default(0);
            $table->string('status', 25)->default('pending');
            $table->string('deployment_status', 25)->nullable();
            $table->timestamp('deployment_started_at')->nullable();
            $table->text('pending_deployment');
            $table->tinyInteger('under_maintenance')->default(0);
            $table->longText('meta');
            $table->timestamps();
        });
    }
}
