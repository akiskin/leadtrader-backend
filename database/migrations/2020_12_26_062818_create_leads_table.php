<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sell_campaign_id');

            $table->json('info')->nullable(); //DocID, etc - but not name, phone, .. (pers data)
            $table->json('metrics')->nullable(); //DM values, dates (creation, last trans) - all used in buy rules

            $table->string('data_path')->nullable(); //path to s3 zip file; TEMP: json with pers data
            $table->string('data_secret')->nullable();

            $table->unsignedTinyInteger('status')->default(0);
            $table->timestamp('status_modified_at')->nullable();

            $table->timestamps();

            $table->foreign('sell_campaign_id')->references('id')->on('sell_campaigns')->onUpdate('CASCADE')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leads');
    }
}
