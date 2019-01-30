# Laravel Wallet

Some apps require a prepayment system like a virtual wallet where customers can recharge credits which they can then use to pay in app stuff.
With this package you can equip your eloquent models with one or multiple digital wallets that handle that for you. 
All the wallet activities are tracked with transactions.

## Installation

Install the package with composer:

```bash
composer require depsimon/laravel-wallet
```

## Run Migrations

Per default the package will automatically load the migrations from
the vendor folder.

If you want more flexibility, you can publish the migration files to your own
migration directory with the following artisan command:

```bash
php artisan vendor:publish --provider="Depsimon\Wallet\WalletServiceProvider" --tag=migrations
```
Make sure to deactivate automatic migration loadingby setting
the config variable `load_migrations` to false when you have
published the migration file.

## Configuration

You can publish the config file with this artisan command:

```bash
php artisan vendor:publish --provider="Depsimon\Wallet\WalletServiceProvider" --tag=config
```

This will merge the `wallet.php` config file where you can specify the Users, Wallets & Transactions classes if you have custom ones.

## Usage

Add the `HasWallet` trait to your User model.

``` php

use Depsimon\Wallet\Traits\HasWallet;

class User extends Model
{
    use HasWallet;

    ...
}
```

Then you can easily make transactions from your user model.

``` php
$user = User::find(1);
$user->wallet->balance; // 0

$user->wallet->deposit(100);
$user->wallet->balance; // 100

$user->wallet->withdraw(50);
$user->wallet->balance; // 50

$user->wallet->forceWithdraw(200);
$user->wallet->balance; // -150
```

You can easily add meta information to the transactions to suit your needs.

``` php
$user = User::find(1);
$user->wallet->deposit(100, ['stripe_source' => 'ch_BEV2Iih1yzbf4G3HNsfOQ07h', 'description' => 'Deposit of 100 credits from Stripe Payment']);
$user->wallet->withdraw(10, ['description' => 'Purchase of Item #1234']);
```
## Testing

This package makes use of https://github.com/orchestral/testbench to create a
laravel testing environment.
The tests will execute with a pre-configured in-memory sqlite database, so you don't need setup a database on your own.

To run the tests just make sure to install the dependencies via `composer install` first and then execute either `vendor/bin/phpunit` or `composer test` from within the project root directory.

## Security

If you discover any security related issues, please email simon@webartisan.be instead of using the issue tracker.

## Credits

- [Simon Depelchin](https://github.com/depsimon)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
