<?php

namespace MannikJ\Laravel\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use MannikJ\Laravel\Wallet\Contracts\ValidModelConstructor;
use MannikJ\Laravel\Wallet\Exceptions\UnacceptedTransactionException;
use MannikJ\Laravel\Wallet\Facades\WalletFacade;

/**
 * @property int       $id
 * @property ?int      $owner
 * @property ?string   $owner_type
 * @property int|float $balance
 * @property Carbon    $created_at
 * @property Carbon    $updated_at
 * @property Carbon    $deleted_at
 */
class Wallet extends Model implements ValidModelConstructor
{
    use SoftDeletes;

    protected $attributes = [
        'balance' => 0,
    ];

    public function __construct(array $attributes = [])
    {
        $type = config('wallet.column_type');

        $this->casts['balance'] = $type === 'decimal'
            ? 'float'
            : 'integer';

        parent::__construct($attributes);
    }

    /**
     * Retrieve all transactions.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(config('wallet.transaction_model', Transaction::class));
    }

    /**
     * Retrieve owner.
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Move credits to this account.
     *
     * @param  int  $amount
     * @return MannikJ\Laravel\Wallet\Models\Transaction
     */
    public function deposit(float $amount, array $meta = [], string $type = 'deposit', bool $forceFail = false): Transaction
    {
        $accepted = $amount >= 0
            && !$forceFail ? true : false;

        if (!$this->exists) {
            $this->save();
        }

        $transaction = $this->transactions()
            ->create([
                'amount' => $amount,
                'type' => $type,
                'meta' => $meta,
                'deleted_at' => $accepted ? null : now(),
            ]);

        if (!$accepted && !$forceFail) {
            throw new UnacceptedTransactionException($transaction, 'Deposit not accepted!');
        }

        $this->refresh();

        return $transaction;
    }

    /**
     * Fail to move credits to this account.
     *
     * @param  float  $amount
     * @return MannikJ\Laravel\Wallet\Models\Transaction
     */
    public function failDeposit(float $amount, array $meta = [], string $type = 'deposit'): Transaction
    {
        return $this->deposit($amount, $meta, $type, true);
    }

    /**
     * Attempt to move credits from this account.
     *
     * @param  float  $amount Only the absolute value will be considered
     * @return MannikJ\Laravel\Wallet\Models\Transaction
     */
    public function withdraw(float $amount, array $meta = [], string $type = 'withdraw', bool $guarded = true)
    {
        $accepted = $guarded
            ? $this->canWithdraw($amount)
            : true;

        if (!$this->exists) {
            $this->save();
        }

        $transaction = $this->transactions()
            ->create([
                'amount' => $amount,
                'type' => $type,
                'meta' => $meta,
                'deleted_at' => $accepted ? null : now(),
            ]);

        if (!$accepted) {
            throw new UnacceptedTransactionException($transaction, 'Withdrawal not accepted due to insufficient funds!');
        }

        $this->refresh();

        return $transaction;
    }

    /**
     * Move credits from this account.
     *
     * @param  float  $amount
     */
    public function forceWithdraw(int|float $amount, array $meta = [], string $type = 'withdraw')
    {
        return $this->withdraw($amount, $meta, $type, false);
    }

    /**
     * Determine if the user can withdraw the given amount.
     *
     * @param  float  $amount
     * @return bool
     */
    public function canWithdraw(float $amount = null)
    {
        return $amount
            ? $this->balance >= abs($amount)
            : $this->balance > 0;
    }

    /**
     * Set wallet balance to desired value.
     * Will automatically create the necessary transaction.
     *
     * @param  float  $balance
     * @param  string  $comment
     * @return MannikJ\Laravel\Wallet\Models\Transaction
     */
    public function setBalance(float $amount, string $comment = 'Manual offset transaction')
    {
        $actualBalance = $this->actualBalance();
        $difference = $amount - $actualBalance;

        if ($difference == 0) {
            return;
        }

        $type = $difference > 0
            ? 'deposit'
            : 'forceWithdraw';

        $this->balance = $actualBalance;
        $this->save();

        return $this->{$type}($difference, ['comment' => $comment]);
    }

    /**
     * Returns the actual balance for this wallet.
     * Might be different from the balance property if the database is manipulated.
     *
     * @param  bool  $save
     * @return float balance
     */
    public function actualBalance(bool $save = false): float
    {
        $undefined = $this->transactions()
            ->whereNotIn('type', WalletFacade::biasedTransactionTypes())
            ->sum('amount');

        $credits = $this->transactions()
            ->whereIn('type', WalletFacade::addingTransactionTypes())
            ->sum(DB::raw('ABS(amount)'));

        $debits = $this->transactions()
            ->whereIn('type', WalletFacade::subtractingTransactionTypes())
            ->sum(DB::raw('ABS(amount)'));

        $balance = $undefined + $credits - $debits;

        if ($save) {
            $this->balance = $balance;
            $this->save();
        }

        return $balance;
    }
}
