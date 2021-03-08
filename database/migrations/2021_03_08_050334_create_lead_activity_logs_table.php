<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeadActivityLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lead_activity_logs', function (Blueprint $table) {
            $table->id();

            $table->uuid('lead_id');
            $table->unsignedTinyInteger('action');
            $table->json('message');

            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lead_activity_logs');
    }
}
