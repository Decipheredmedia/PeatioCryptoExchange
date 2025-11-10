<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    const TYPE_LIMIT = 'limit';
    const TYPE_MARKET = 'market';

    const STATE_WAIT = 'wait';
    const STATE_DONE = 'done';
    const STATE_CANCEL = 'cancel';

    protected $fillable = [
        'member_id',
        'market_id',
        'price',
        'volume',
        'origin_volume',
        'state',
        'type',
        'side',
        'locked',
        'origin_locked',
        'trades_count',
    ];

    protected $casts = [
        'price' => 'decimal:16',
        'volume' => 'decimal:16',
        'origin_volume' => 'decimal:16',
        'locked' => 'decimal:16',
        'origin_locked' => 'decimal:16',
        'trades_count' => 'integer',
    ];

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function market()
    {
        return $this->belongsTo(Market::class);
    }

    public function trades()
    {
        return $this->hasMany(Trade::class);
    }

    public function isBid()
    {
        return $this->side === 'buy';
    }

    public function isAsk()
    {
        return $this->side === 'sell';
    }

    public function isLimit()
    {
        return $this->type === self::TYPE_LIMIT;
    }

    public function isMarket()
    {
        return $this->type === self::TYPE_MARKET;
    }

    public function strike($trade)
    {
        $this->volume -= $trade->volume;
        $this->locked -= $trade->volume * ($this->isBid() ? $trade->price : 1);
        $this->trades_count++;
        
        if ($this->volume <= 0) {
            $this->state = self::STATE_DONE;
        }
        
        $this->save();
    }
}
