<?php

namespace MannikJ\Laravel\Wallet\Tests\Factories;

use MannikJ\Laravel\Wallet\Models\Wallet;
use MannikJ\Laravel\Wallet\Tests\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WalletFactory extends Factory
{

    protected $model = Wallet::class;

    public function definition()
    {
        return [
            'owner_id' => UserFactory::new(),
            'owner_type' => User::class
        ];
    }
}
