<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeploymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deployments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('stack_id')->index();
            $table->unsignedInteger('initiator_id')->nullable();
            $table->string('branch')->nullable();
            $table->string('commit_hash');
            $table->text('build_commands');
            $table->text('activation_commands');
            $table->text('directories');
            $table->text('daemons');
            $table->text('schedule');
            $table->tinyInteger('activated')->default(0);
            $table->string('status', 25)->default('pending');
            $table->timestamps();

            $table->index('created_at');
        });
    }
}
