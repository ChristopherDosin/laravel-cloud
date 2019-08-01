<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDatabaseRestoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('database_restores', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('database_id')->index();
            $table->unsignedInteger('database_backup_id')->index();
            $table->string('database_name');
            $table->string('status', 25)->default('pending');
            $table->integer('exit_code')->nullable();
            $table->longText('output');
            $table->timestamps();

            $table->foreign('database_backup_id')
                  ->references('id')
                  ->on('database_backups')
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
        Schema::dropIfExists('database_restores');
    }
}
