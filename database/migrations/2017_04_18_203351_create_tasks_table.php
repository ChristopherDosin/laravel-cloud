<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('project_id')->index();
            $table->unsignedInteger('provisionable_id');
            $table->string('provisionable_type');
            $table->string('name');
            $table->string('user', 25);
            $table->string('status', 25)->default('pending');
            $table->integer('exit_code')->nullable();
            $table->longText('script');
            $table->longText('output');
            $table->text('options');
            $table->timestamps();

            $table->index(['provisionable_id', 'provisionable_type']);
            $table->index('created_at');
        });
    }
}
