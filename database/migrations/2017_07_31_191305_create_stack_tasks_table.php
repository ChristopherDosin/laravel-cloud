<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStackTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stack_tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('stack_id')->index();
            $table->string('name');
            $table->string('user');
            $table->longText('commands');
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stack_tasks');
    }
}
