<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBuyCampaignToClientBalanceDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_balance_details', function (Blueprint $table) {
            $table->uuid('buy_campaign_id')->nullable();
            $table->foreign('buy_campaign_id')->references('id')->on('buy_campaigns')->onUpdate('CASCADE')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_balance_details', function (Blueprint $table) {
            $table->removeColumn('buy_campaign_id');
            $table->dropConstrainedForeignId('buy_campaign_id');
        });
    }
}
