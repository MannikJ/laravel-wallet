<?php

use MannikJ\Laravel\Wallet\Models\Transaction;
use MannikJ\Laravel\Wallet\Models\Wallet;

return [
    /*
     * Disable auto-loading of the vendor migrations
     * You can then publish the migrations and
     * change them for more flexibility
     */
    'load_migrations' => env('WALLET_LOAD_MIGRATIONS', true),
    /*
     * Change this to specify the money amount column types
     *
     * @deprecated Trying to support multiple column types is too complex and makes it harder to improve the package.
     *             Will be removed in the next version
     */
    'column_type' => env('WALLET_COLUMN_TYPE', 'decimal'),

    /*
     * Change this if you need to extend the default Wallet Model
     */
    'wallet_model' => Wallet::class,

    /*
     * Change this if you need to extend the default Transaction Model
     */
    'transaction_model' => Transaction::class,

    /*
     * Transaction types that are subtracted from the wallet balance.
     * All amounts will be converted to a positive value
     */
    'adding_transaction_types' => ['deposit', 'refund'],

    /*
     * Transaction types that are subtracted from the wallet balance
     * All amounts will be converted to a negative value
     */
    'subtracting_transaction_types' => ['withdraw', 'payout'],

    /*
     * Transaction types that can be positive or negative
     * Per default all types that are not explicitly specified
     * as positive or negative are treated as unbiased types,
     * so their sign is determined by the amount.
     * You may find it helpful to explicitly specify unbiased types here,
     * making it easier to display available options.
     */
    'unbiased_transaction_types' => [],
];
