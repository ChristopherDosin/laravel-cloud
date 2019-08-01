<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAlertsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('project_id')->index();
            $table->unsignedInteger('stack_id')->nullable()->index();
            $table->string('level', 15)->default('error');
            $table->string('type');
            $table->text('exception');
            $table->text('meta');
            $table->timestamps();
        });
    }
}
