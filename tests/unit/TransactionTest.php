<?php

namespace MannikJ\Laravel\Wallet\Tests\Unit;

use MannikJ\Laravel\Wallet\Models\Wallet;
use MannikJ\Laravel\Wallet\Exceptions\UnacceptedTransactionException;
use MannikJ\Laravel\Wallet\Tests\TestCase;
use MannikJ\Laravel\Wallet\Tests\Models\User;
use MannikJ\Laravel\Wallet\Models\Transaction;
use Illuminate\Support\Collection;

class TransactionTest extends TestCase
{

    /** @test */
    public function wallet()
    {
        $transaction = factory(Transaction::class)->create();
        $this->assertInstanceOf(Wallet::class, $transaction->wallet);
    }

    /** @test */
    public function origin()
    {
        $origin = factory(Transaction::class)->create();
        $transaction = factory(Transaction::class)->create();
        $transaction->origin()->associate($origin);
        $transaction->save();
        $transaction = $transaction->fresh();
        $this->assertInstanceOf(Transaction::class, $transaction->origin);
        $this->assertTrue($origin->is($transaction->origin));
    }

    /** @test */
    public function children()
    {
        $origin = factory(Transaction::class)->create();
        $transaction = factory(Transaction::class)->create();
        $origin->children()->save($transaction);
        $this->assertInstanceOf(Collection::class, $transaction->children);
        $child = $origin->children()->where('id', $transaction->id)->first();
        $this->assertTrue($transaction->is($child));
        $this->assertTrue($origin->is($transaction->origin));
    }

    /** @test */
    public function reference()
    {

        $transaction = factory(Transaction::class)->create();
        $this->assertNull($transaction->reference);
        $transaction->reference()->associate($transaction->wallet);
        $this->assertTrue($transaction->wallet->is($transaction->reference));
    }

    /** @test */
    public function update()
    {
        $transaction = factory(Transaction::class)->create(['amount' => 20, 'type' => 'deposit']);
        $this->assertEquals(20, $transaction->wallet->balance);
        $transaction->update(['amount' => 100]);
        $this->assertEquals(100, $transaction->wallet->refresh()->balance);
        $transaction->update(['amount' => 20]);
        $this->assertEquals(20, $transaction->wallet->refresh()->balance);
        $transaction->update(['amount' => -20]);
        $this->assertEquals(20, $transaction->wallet->refresh()->balance);
        $transaction->update(['amount' => -20, 'type' => 'withdraw']);
        $this->assertEquals(-20, $transaction->wallet->refresh()->balance);
    }

    /** @test */
    public function create_converts_amount_to_absolute_value()
    {
        $wallet = factory(Wallet::class)->create();
        $transaction = $wallet->transactions()->create(['type' => 'withdraw', 'amount' => -20]);
        $this->assertEquals(20, $transaction->getAttributes()['amount']);
    }

    /** @test */
    public function delete_model()
    {
        $transaction = factory(Transaction::class)->create(['amount' => 20, 'type' => 'deposit']);
        $this->assertEquals(20, $transaction->wallet->refresh()->balance);
        $transaction->delete();
        $this->assertTrue($transaction->trashed());
        $this->assertEquals(0, $transaction->wallet->refresh()->balance);
        $transaction = factory(Transaction::class)->create(['amount' => 20, 'type' => 'withdraw']);
        $this->assertEquals(-20, $transaction->wallet->refresh()->balance);
    }

    /** @test */
    public function replace()
    {
        $timestamp = now()->subHours(1);
        $transaction = factory(Transaction::class)->create([
            'amount' => 20,
            'type' => 'deposit',
            'created_at' => $timestamp,
            'updated_at' => $timestamp
        ]);
        $this->assertTrue($timestamp->diffInSeconds($transaction->refresh()->updated_at) < 1);
        $this->assertEquals(20, $transaction->wallet->refresh()->balance);
        $replacement = $transaction->replace(['amount' => 100]);
        $this->assertEquals(100, $transaction->wallet->refresh()->balance);
        $this->assertEquals(2, Transaction::withTrashed()->count());
        $this->assertTrue($transaction->refresh()->created_at->diffInSeconds($replacement->refresh()->created_at) < 1);
        $this->assertTrue($transaction->is($replacement->origin));
        $this->assertTrue($replacement->origin->trashed());
    }

    /** @test */
    public function generated_hash_is_set()
    {
        $transaction = factory(Transaction::class)->create();
        $this->assertNotNull($transaction->hash);
        $transactions = factory(Transaction::class, 2)->create();
        $transactions->each(function ($transaction) {
            $this->assertNotNull($transaction->hash);
        });
    }

    /** @test */
    public function get_total_amount()
    {
        $transaction = factory(Transaction::class)->states('deposit')->create(['amount' => '5']);
        $children = factory(Transaction::class, 3)->states('withdraw')->create(['amount' => '1', 'origin_id' => $transaction->id]);
        $price = 2;
        $this->assertEquals($price, $transaction->getTotalAmount());
        $this->assertEquals($price, $transaction->getAttributes()['total_amount']);
        $transaction = factory(Transaction::class)->states('withdraw')->create(['amount' => '5']);
        $children = factory(Transaction::class, 3)->states('deposit')->create(['amount' => '1', 'origin_id' => $transaction->id]);
        $price = -2;
        $this->assertEquals($price, $transaction->getTotalAmount());
        $this->assertEquals($price, $transaction->getAttributes()['total_amount']);
        $transaction = factory(Transaction::class)->states('withdraw')->create(['amount' => '5']);
        $children = factory(Transaction::class, 3)->states('withdraw')->create(['amount' => '1', 'origin_id' => $transaction->id]);
        $price = 8;
        $this->assertEquals(-$price, $transaction->getTotalAmount());
        $transaction = factory(Transaction::class)->states('deposit')->create(['amount' => '5']);
        $children = factory(Transaction::class, 3)->states('deposit')->create(['amount' => '1', 'origin_id' => $transaction->id]);
        $price = 8;
        $this->assertEquals($price, $transaction->getTotalAmount());
        $children->first()->delete();
        $price = 7;
        $this->assertEquals($price, $transaction->getTotalAmount());
    }

    /** @test */
    public function scope_select_total_amount()
    {
        $transaction = factory(Transaction::class)->states('deposit')->create(['amount' => '5']);
        $children = factory(Transaction::class, 3)->states('withdraw')->create(['amount' => '1', 'origin_id' => $transaction->id]);
        $price = 2;
        $this->assertEquals($price, $transaction->where('id', $transaction->id)->selectTotalAmount()->first()->getAttributes()['total_amount']);
        $transaction = factory(Transaction::class)->states('withdraw')->create(['amount' => '5']);
        $children = factory(Transaction::class, 3)->states('deposit')->create(['amount' => '1', 'origin_id' => $transaction->id]);
        $price = -2;
        $this->assertEquals($price, $transaction->where('id', $transaction->id)->selectTotalAmount()->first()->getAttributes()['total_amount']);
        $transaction = factory(Transaction::class)->states('withdraw')->create(['amount' => '5']);
        $children = factory(Transaction::class, 3)->states('withdraw')->create(['amount' => '1', 'origin_id' => $transaction->id]);
        $price = 8;
        $this->assertEquals(-$price, $transaction->where('id', $transaction->id)->selectTotalAmount()->first()->getAttributes()['total_amount']);
        $transaction = factory(Transaction::class)->states('deposit')->create(['amount' => '5']);
        $children = factory(Transaction::class, 3)->states('deposit')->create(['amount' => '1', 'origin_id' => $transaction->id]);
        $price = 8;
        $this->assertEquals($price, $transaction->where('id', $transaction->id)->selectTotalAmount()->first()->getAttributes()['total_amount']);
        $children->first()->delete();
        $price = 7;
        $this->assertEquals($price, $transaction->where('id', $transaction->id)->selectTotalAmount()->first()->getAttributes()['total_amount']);
    }

    /** @test */
    public function get_total_amount_attribute()
    {
        $transaction = factory(Transaction::class)->states('deposit')->create(['amount' => '5']);
        $children = factory(Transaction::class, 3)->states('withdraw')->create(['amount' => '1', 'origin_id' => $transaction->id]);
        $price = 2;
        $this->assertEquals($price, $transaction->getTotalAmountAttribute());
        $this->assertEquals($price, $transaction->total_amount);
        $this->assertEquals(4, Transaction::count());
        $transaction = factory(Transaction::class)->states('withdraw')->create(['amount' => '5']);
        $children = factory(Transaction::class, 3)->states('deposit')->create(['amount' => '1', 'origin_id' => $transaction->id]);
        $price = -2;
        $this->assertEquals($price, $transaction->getTotalAmountAttribute());
        $this->assertEquals($price, $transaction->total_amount);
        $transaction = factory(Transaction::class)->states('withdraw')->create(['amount' => '5']);
        $children = factory(Transaction::class, 3)->states('withdraw')->create(['amount' => '1', 'origin_id' => $transaction->id]);
        $price = -8;
        $this->assertEquals($price, $transaction->getTotalAmountAttribute());
        $this->assertEquals($price, $transaction->total_amount);
        $transaction = factory(Transaction::class)->states('deposit')->create(['amount' => '5']);
        $children = factory(Transaction::class, 3)->states('deposit')->create(['amount' => '1', 'origin_id' => $transaction->id]);
        $price = 8;
        $this->assertEquals($price, $transaction->getTotalAmountAttribute());
        $this->assertEquals($price, $transaction->total_amount);
    }

}

