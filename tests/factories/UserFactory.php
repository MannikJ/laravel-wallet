<?php

use Illuminate\Support\Str;
use Faker\Generator as Faker;
use MannikJ\Laravel\Wallet\Tests\Models\User;

$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => bcrypt('test'),
        'remember_token' => Str::random(10),
    ];
});