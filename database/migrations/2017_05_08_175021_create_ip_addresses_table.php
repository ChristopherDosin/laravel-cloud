<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIpAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ip_addresses', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('addressable_id');
            $table->string('addressable_type');
            $table->string('public_address');
            $table->string('private_address');
            $table->timestamps();

            $table->index(['addressable_id', 'addressable_type']);
            $table->index('public_address');
        });
    }
}
