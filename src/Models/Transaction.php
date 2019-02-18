<?php

namespace Depsimon\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $table = 'wallet_transactions';

    protected $attributes = [
        'meta' => '{}',
    ];

    protected $fillable = [
        'wallet_id', 'amount', 'type', 'meta', 'deleted_at'
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        $type = config('wallet.column_type');
        if ($type == 'decimal') {
            $this->casts['amount'] = 'float';
        } else if ($type == 'integer') {
            $this->casts['amount'] = 'integer';
        }
        parent::__construct($attributes);
    }

    /**
     * Retrieve the wallet of this transaction
     */
    public function wallet()
    {
        return $this->belongsTo(config('wallet.wallet_model', Wallet::class))->withTrashed();
    }

    /**
     * Retrieve the original version of the transaction (if it has been replaced)
     */
    public function origin()
    {
        return $this->belongsTo(config('wallet.transaction_model', Transaction::class))->withTrashed();
    }

    /**
     * Retrieve child transactions
     */
    public function children()
    {
        return $this->hasMany(config('wallet.transaction_model', Transaction::class), 'origin_id');
    }

    /**
     * Retrieve optional reference model
     */
    public function reference()
    {
        return $this->morphTo();
    }

    /**
     * Creates a replication and updates it with the new
     * attributes, adds the old as origin relation
     * and then soft deletes the old.
     * Be careful if the old transaction was referenced
     * by other models.
     */
    public function replace($attributes)
    {
        return \DB::transaction(function () use ($attributes) {
            $newTransaction = $this->replicate();
            $newTransaction->created_at = $this->created_at;
            $newTransaction->fill($attributes);
            $newTransaction->origin()->associate($this);
            $newTransaction->save();
            $this->delete();
            return $newTransaction;
        });
    }

    public function getAmountAttribute()
    {
        return $this->getAmountWithSign();
    }

    public function setAmountAttribute($amount)
    {
        if ($this->shouldConvertToAbsoluteAmount()) {
            $amount = abs($amount);
        }
        $this->attributes['amount'] = ($amount);
    }

    public function getAmountWithSign($amount = null, $type = null)
    {
        $amount = $amount ? : array_get($this->attributes, 'amount');
        $type = $type ? : $this->type;
        $amount = $this->shouldConvertToAbsoluteAmount() ? abs($amount) : $amount;
        if (in_array($type, config('wallet.subtracting_transaction_types', []))) {
            return $amount * -1;
        }
        return $amount;
    }

    public function shouldConvertToAbsoluteAmount($type = null)
    {
        $type = $type ? : $this->type;
        return in_array($type, config('wallet.subtracting_transaction_types', [])) ||
            in_array($type, config('wallet.adding_transaction_types', []));
    }

    public function getTotalAmount()
    {
        // $totalAmount = $this->amount + $this->children()->get()->sum('amount');
        $totalAmount = $this->where('id', $this->id)->selectTotalAmount()->first();
        $totalAmount = $totalAmount ? array_get($totalAmount->getAttributes(), 'total_amount') : null;
        $this->attributes['total_amount'] = $totalAmount;
        return $totalAmount;
    }

    public static function getSignedAmountRawSql($table = null)
    {
        $table = $table ? : (new static())->getTable();
        $subtractingTypes = implode(',', array_map(
            function ($type) {
                return "'{$type}'";
            },
            config('wallet.subtracting_transaction_types')
        ));
        $addingTypes = implode(',', array_map(
            function ($type) {
                return "'{$type}'";
            },
            config('wallet.adding_transaction_types')
        ));
        return "CASE
                WHEN {$table}.type
                    IN ({$addingTypes})
                    THEN abs({$table}.amount)
                WHEN {$table}.type
                    IN ({$subtractingTypes})
                    THEN abs({$table}.amount)*-1
                ELSE {$table}.amount
                END";
    }

    public static function getChildTotalAmountRawSql($table = 'children')
    {
        $signedAmountRawSql = static::getSignedAmountRawSql($table);
        $transactionsTable = (new static())->getTable();
        return "IFNULL((
                    SELECT sum({$signedAmountRawSql})
                    FROM {$transactionsTable} AS {$table}
                    WHERE {$table}.origin_id = {$transactionsTable}.id
                    AND {$table}.deleted_at IS NULL
                ),0)";
    }

    public static function getTotalAmountRawSql()
    {
        // TODO: total_amount cannot be queried in where
        $signedAmountRawSql = static::getSignedAmountRawSql();
        $childTotalAmount = static::getChildTotalAmountRawSql();
        return "(
                    IFNULL(
                        (
                            SELECT {$signedAmountRawSql}
                        ),0
                    )
                    +
                    {$childTotalAmount}
                )";
    }

    public function scopeSelectTotalAmount($query)
    {
        return $query->addSelect(\DB::raw($this->getTotalAmountRawSql() . 'AS total_amount'));
    }


    public function getTotalAmountAttribute()
    {
        $totalAmount = array_get($this->attributes, 'total_amount', $this->getTotalAmount());
        return $totalAmount;
    }


}