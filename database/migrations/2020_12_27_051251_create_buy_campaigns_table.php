<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBuyCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buy_campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->default('');

            $table->date('start')->nullable();
            $table->date('finish')->nullable();

            $table->uuid('product_id');
            $table->uuid('client_id');

            $table->unsignedTinyInteger('status')->default(0);
            $table->timestamp('status_modified_at')->nullable();

            $table->unsignedDecimal('budget')->default(0);
            $table->unsignedDecimal('max_price')->default(0);
            $table->json('buy_rules')->nullable();

            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onUpdate('CASCADE')->onDelete('RESTRICT');
            $table->foreign('client_id')->references('id')->on('clients')->onUpdate('CASCADE')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('buy_campaigns');
    }
}
