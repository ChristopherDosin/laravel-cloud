<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServerTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('server_tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('stack_task_id')->index();
            $table->unsignedInteger('taskable_id');
            $table->string('taskable_type');
            $table->unsignedInteger('task_id')->nullable();
            $table->longText('commands');
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->foreign('stack_task_id')
                  ->references('id')
                  ->on('stack_tasks')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('server_tasks');
    }
}
