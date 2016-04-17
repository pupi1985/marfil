<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWorkUnitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_units', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('part');
            $table->datetime('assigned_at')->nullable();
            $table->integer('crack_request_id')->unsigned();
            $table->foreign('crack_request_id')->references('id')->on('crack_requests')->onDelete('cascade');
            $table->integer('dictionary_id')->unsigned();
            $table->foreign('dictionary_id')->references('id')->on('dictionaries')->onDelete('cascade');

            $table->index(['assigned_at', 'part']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('work_units');
    }
}
