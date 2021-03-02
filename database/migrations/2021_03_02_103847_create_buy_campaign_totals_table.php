<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBuyCampaignTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buy_campaign_totals', function (Blueprint $table) {
            $table->uuid('buy_campaign_id')->primary();
            $table->decimal('amount');

            $table->foreign('buy_campaign_id')->references('id')->on('buy_campaigns')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('buy_campaign_totals');
    }
}
