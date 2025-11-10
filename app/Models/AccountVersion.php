<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountVersion extends Model
{
    use HasFactory;

    public $timestamps = true;
    
    protected $fillable = [
        'member_id',
        'account_id',
        'reason',
        'balance',
        'locked',
        'fee',
        'amount',
        'modifiable_id',
        'modifiable_type',
        'currency_id',
        'fun',
    ];

    protected $casts = [
        'balance' => 'decimal:16',
        'locked' => 'decimal:16',
        'fee' => 'decimal:16',
        'amount' => 'decimal:16',
    ];

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function modifiable()
    {
        return $this->morphTo();
    }
}
