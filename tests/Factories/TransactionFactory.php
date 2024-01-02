<?php

namespace MannikJ\Laravel\Wallet\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use MannikJ\Laravel\Wallet\Models\Transaction;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition()
    {
        return [
            'wallet_id' => WalletFactory::new(),
            'type' => $this->faker->randomElement([
                'deposit',
                'withdraw',
            ]),
            'amount' => $this->faker->randomFloat(4, 0, 10000),
        ];
    }

    public function withdraw()
    {
        return $this->state(function (array $attributes) {
            return ['type' => 'withdraw'];
        });
    }

    public function deposit()
    {
        return $this->state(function (array $attributes) {
            return ['type' => 'deposit'];
        });
    }
}
