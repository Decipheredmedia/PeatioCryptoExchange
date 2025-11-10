<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'coin',
        'precision',
        'quick_withdraw_limit',
        'withdraw_fee',
        'deposit_fee',
        'min_deposit_amount',
        'min_withdraw_amount',
        'visible',
        'deposit_enabled',
        'withdraw_enabled',
        'base_factor',
    ];

    protected $casts = [
        'coin' => 'boolean',
        'visible' => 'boolean',
        'deposit_enabled' => 'boolean',
        'withdraw_enabled' => 'boolean',
        'withdraw_fee' => 'decimal:16',
        'deposit_fee' => 'decimal:16',
        'min_deposit_amount' => 'decimal:16',
        'min_withdraw_amount' => 'decimal:16',
        'quick_withdraw_limit' => 'decimal:16',
        'base_factor' => 'integer',
    ];

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }

    public function withdraws()
    {
        return $this->hasMany(Withdraw::class);
    }

    public function markets()
    {
        return $this->hasMany(Market::class, 'ask_currency_id')
                    ->orWhere('bid_currency_id', $this->id);
    }
}
