<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPolymorphicRelationToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $transactionModelClass = config('wallet.transaction_model');
        $transactionTable = (new $transactionModelClass())->getTable();
        Schema::table($transactionTable, function (Blueprint $table) {
            $table->string('reference_type')
                ->nullable()
                ->after('wallet_id');
        });
        Schema::table($transactionTable, function (Blueprint $table) {
            $table->unsignedInteger('reference_id')
                ->nullable()
                ->after('wallet_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $transactionModelClass = config('wallet.transaction_model');
        $transactionTable = (new $transactionModelClass())->getTable();
        Schema::table($transactionTable, function (Blueprint $table) {
            $table->dropColumn('reference_type');
        });
        Schema::table($transactionTable, function (Blueprint $table) {
            $table->dropColumn('reference_id');
        });
    }
}
