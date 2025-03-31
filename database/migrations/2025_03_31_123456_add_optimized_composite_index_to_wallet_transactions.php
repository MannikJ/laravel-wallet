<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $foreignIndex = [
        'wallet_id',
    ];

    private array $newIndex = [
        'wallet_id',
        'deleted_at',
        'type',
        'amount',
    ];

    private string $tableName;

    public function __construct()
    {
        $this->tableName = $this->getTableName();
    }

    protected function getTableName(): string
    {
        $walletModelClass = config('wallet.wallet_model');
        $walletTable = (new $walletModelClass)->getTable();

        return $walletTable;
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->index($this->newIndex);
            $table->dropForeign($this->foreignIndex);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->foreign($this->foreignIndex);
            $table->dropIndex($this->newIndex);
        });
    }
};
