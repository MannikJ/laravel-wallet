<?php

use Faker\Generator as Faker;
use Illuminate\Support\Arr;
use MannikJ\Laravel\Wallet\Models\Wallet;
use MannikJ\Laravel\Wallet\Tests\Models\User;

$factory->define(Wallet::class, function (Faker $faker, $attributes) {
    $owner = Arr::has($attributes, 'owner_id')
        ? User::findOrFail($attributes['owner_id'])
        : (User::first() ? : factory(User::class)->create());
    return [
        'owner_id' => $owner->id,
        'owner_type' => get_class($owner),
    ];
});