<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServerDeploymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('server_deployments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('deployment_id')->index();
            $table->unsignedInteger('deployable_id');
            $table->string('deployable_type');
            $table->unsignedInteger('build_task_id')->nullable();
            $table->unsignedInteger('activation_task_id')->nullable();
            $table->text('build_commands');
            $table->text('activation_commands');
            $table->string('status', 25)->default('running');
            $table->timestamps();

            $table->index(['deployable_id', 'deployable_type']);

            $table->foreign('deployment_id')
                  ->references('id')
                  ->on('deployments')
                  ->onDelete('cascade');
        });
    }
}
