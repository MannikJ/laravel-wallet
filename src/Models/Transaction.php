<?php

namespace MannikJ\Laravel\Wallet\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use MannikJ\Laravel\Wallet\Contracts\ValidModelConstructor;
use MannikJ\Laravel\Wallet\Facades\WalletFacade;

/**
 * A model which stores wallet transactions
 *
 * @property int $id
 * @property int $wallet_id
 * @property int $origin_id
 * @property int|null $reference_id
 * @property string|null $reference_type
 * @property string $type
 * @property string $hash
 * @property string $type
 * @property array $meta
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 */
class Transaction extends Model implements ValidModelConstructor
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'wallet_transactions';

    protected $attributes = [
        'meta' => '{}',
    ];

    protected $fillable = [
        'wallet_id', 'amount', 'type', 'meta', 'deleted_at',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        $type = config('wallet.column_type');
        if ($type == 'decimal') {
            $this->casts['amount'] = 'float';
        } elseif ($type == 'integer') {
            $this->casts['amount'] = 'integer';
        }
        parent::__construct($attributes);
    }

    /**
     * Retrieve the wallet of this transaction
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(config('wallet.wallet_model', Wallet::class))->withTrashed();
    }

    /**
     * Retrieve the original version of the transaction (if it has been replaced)
     */
    public function origin(): BelongsTo
    {
        return $this->belongsTo(config('wallet.transaction_model', Transaction::class))->withTrashed();
    }

    /**
     * Retrieve child transactions
     */
    public function children(): HasMany
    {
        return $this->hasMany(config('wallet.transaction_model', Transaction::class), 'origin_id');
    }

    /**
     * Retrieve optional reference model
     */
    public function reference(): MorphTo
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
    public function replace($attributes): Transaction
    {
        return DB::transaction(function () use ($attributes) {
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

    public function setAmountAttribute(int|float $amount)
    {
        if ($this->shouldConvertToAbsoluteAmount()) {
            $amount = abs($amount);
        }
        $this->attributes['amount'] = ($amount);
    }

    public function getAmountWithSign(null|int|float $amount = null, ?string $type = null): int|float
    {
        $amount = $amount ?: Arr::get($this->attributes, 'amount');
        $type = $type ?: $this->type;
        $amount = $this->shouldConvertToAbsoluteAmount() ? abs($amount) : $amount;
        if (in_array($type, config('wallet.subtracting_transaction_types', []))) {
            return $amount * -1;
        }

        return $amount;
    }

    public function shouldConvertToAbsoluteAmount(?string $type = null): bool
    {
        $type = $type ?: $this->type;

        return in_array($type, WalletFacade::subtractingTransactionTypes()) ||
            in_array($type, WalletFacade::addingTransactionTypes());
    }

    public function getTotalAmount(): int|float
    {
        // $totalAmount = $this->amount + $this->children()->get()->sum('amount');
        $totalAmount = $this->where('id', $this->id)->selectTotalAmount()->first();
        $totalAmount = $totalAmount ? Arr::get($totalAmount->getAttributes(), 'total_amount') : null;
        $this->attributes['total_amount'] = $totalAmount;

        return $totalAmount;
    }

    public static function getSignedAmountRawSql(?string $table = null): string
    {
        $table = $table ?: (new static)->getTable();
        $subtractingTypes = implode(',', array_map(
            function ($type) {
                return "'{$type}'";
            },
            WalletFacade::subtractingTransactionTypes()
        ));
        $addingTypes = implode(',', array_map(
            function ($type) {
                return "'{$type}'";
            },
            WalletFacade::addingTransactionTypes()
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

    public static function getChildTotalAmountRawSql(?string $table = 'children'): string
    {
        $signedAmountRawSql = static::getSignedAmountRawSql($table);
        $transactionsTable = (new static)->getTable();

        return "IFNULL((
                    SELECT sum({$signedAmountRawSql})
                    FROM {$transactionsTable} AS {$table}
                    WHERE {$table}.origin_id = {$transactionsTable}.id
                    AND {$table}.deleted_at IS NULL
                ),0)";
    }

    public static function getTotalAmountRawSql(): string
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

    public function scopeSelectTotalAmount(Builder $query): Builder
    {
        return $query->addSelect(DB::raw($this->getTotalAmountRawSql().'AS total_amount'));
    }

    public function getTotalAmountAttribute(): int|float
    {
        $totalAmount = Arr::get($this->attributes, 'total_amount', $this->getTotalAmount());

        return $totalAmount;
    }
}
