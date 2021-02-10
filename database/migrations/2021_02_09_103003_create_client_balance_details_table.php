<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientBalanceDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_balance_details', function (Blueprint $table) {
            $table->unsignedBigInteger('transaction_id');
            $table->timestamp('period', 6);
            $table->uuid('client_id');
            $table->decimal('amount');

            $table->primary(['transaction_id','period','client_id']);
            $table->foreign('transaction_id')->references('id')->on('transactions')->onUpdate('CASCADE')->onDelete('RESTRICT');
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
        Schema::dropIfExists('client_balance_details');
    }
}
