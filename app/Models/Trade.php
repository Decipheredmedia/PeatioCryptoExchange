<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'price',
        'volume',
        'funds',
        'market_id',
        'maker_id',
        'maker_order_id',
        'taker_id',
        'taker_order_id',
        'trend',
    ];

    protected $casts = [
        'price' => 'decimal:16',
        'volume' => 'decimal:16',
        'funds' => 'decimal:16',
    ];

    public function market()
    {
        return $this->belongsTo(Market::class);
    }

    public function maker()
    {
        return $this->belongsTo(User::class, 'maker_id');
    }

    public function taker()
    {
        return $this->belongsTo(User::class, 'taker_id');
    }

    public function makerOrder()
    {
        return $this->belongsTo(Order::class, 'maker_order_id');
    }

    public function takerOrder()
    {
        return $this->belongsTo(Order::class, 'taker_order_id');
    }
}
