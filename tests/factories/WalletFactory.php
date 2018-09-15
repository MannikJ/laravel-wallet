<?php

use Faker\Generator as Faker;
use Depsimon\Wallet\Wallet;
use Depsimon\Wallet\Tests\Models\User;

$factory->define(Wallet::class, function (Faker $faker, $attributes) {
    $owner = array_has($attributes, 'owner_id')
        ? User::findOrFail($attributes['owner_id'])
        : factory(User::class)->create();
    return [
        'owner_id' => $owner->id,
    ];
});