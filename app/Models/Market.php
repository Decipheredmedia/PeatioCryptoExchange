<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    use HasFactory;

    protected $fillable = [
        'ask_currency_id',
        'bid_currency_id',
        'ask_unit',
        'bid_unit',
        'ask_fee',
        'bid_fee',
        'min_ask_price',
        'max_bid_price',
        'min_ask_amount',
        'min_bid_amount',
        'ask_precision',
        'bid_precision',
        'enabled',
        'visible',
    ];

    protected $casts = [
        'ask_fee' => 'decimal:4',
        'bid_fee' => 'decimal:4',
        'min_ask_price' => 'decimal:16',
        'max_bid_price' => 'decimal:16',
        'min_ask_amount' => 'decimal:16',
        'min_bid_amount' => 'decimal:16',
        'enabled' => 'boolean',
        'visible' => 'boolean',
    ];

    public function askCurrency()
    {
        return $this->belongsTo(Currency::class, 'ask_currency_id');
    }

    public function bidCurrency()
    {
        return $this->belongsTo(Currency::class, 'bid_currency_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function trades()
    {
        return $this->hasMany(Trade::class);
    }

    public function getNameAttribute()
    {
        return "{$this->ask_unit}/{$this->bid_unit}";
    }
}
