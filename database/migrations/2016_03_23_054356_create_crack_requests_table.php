<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCrackRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crack_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('bssid');
            $table->boolean('finished');
            $table->datetime('created_at');

            $table->unique('bssid');
            $table->index(['finished', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crack_requests');
    }
}
