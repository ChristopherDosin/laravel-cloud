<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDatabaseBackupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('database_backups', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('database_id')->index();
            $table->unsignedInteger('storage_provider_id');
            $table->string('database_name');
            $table->text('backup_path');
            $table->integer('size')->nullable();
            $table->string('status', 25)->default('pending');
            $table->integer('exit_code')->nullable();
            $table->longText('output');
            $table->timestamps();

            $table->foreign('database_id')
                  ->references('id')
                  ->on('databases')
                  ->onDelete('cascade');

            $table->foreign('storage_provider_id')
                  ->references('id')
                  ->on('storage_providers')
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
        Schema::dropIfExists('database_backups');
    }
}
