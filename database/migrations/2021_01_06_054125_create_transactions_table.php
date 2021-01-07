<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedTinyInteger('type')->default(0);
            $table->json('reference')->nullable();
            $table->json('amounts')->nullable();

            $table->uuid('lead_id')->nullable(); // TODO ?must be unique if not null
            $table->uuid('buy_campaign_id')->nullable();

            $table->foreign('lead_id')->references('id')->on('leads')->onUpdate('CASCADE')->onDelete('RESTRICT');
            $table->foreign('buy_campaign_id')->references('id')->on('buy_campaigns')->onUpdate('CASCADE')->onDelete('RESTRICT');

            $table->timestamps(6); //to microseconds
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
