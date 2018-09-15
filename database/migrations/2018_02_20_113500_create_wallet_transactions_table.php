<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWalletTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('wallet_id');

            if (config('wallet.column_type') == 'decimal') {
                $table->decimal('amount', 12, 4); // amount is an decimal, it could be "dollars" or "cents"
            } else {
                $table->integer('amount');
            }

            $table->string('hash', 60); // hash is a uniqid for each transaction
            $table->string('type', 30); // type can be anything in your app, by default we use "deposit" and "withdraw"
            $table->json('meta')->nullable(); // Add all kind of meta information you need

            $table->timestamps();
            $table->softDeletes();
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wallet_transactions');
    }
}