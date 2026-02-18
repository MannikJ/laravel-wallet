<?php

namespace MannikJ\Laravel\Wallet\Contracts;

interface ValidModelConstructor
{
    public function __construct(array $attributes = []);
}
