<?php

namespace MannikJ\Laravel\Wallet\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as AuthUser;
use MannikJ\Laravel\Wallet\Traits\HasWallet;

class User extends AuthUser
{
    use HasFactory;
    use HasWallet;
}
