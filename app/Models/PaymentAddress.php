<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'currency_id',
        'address',
        'secret',
    ];

    protected $hidden = ['secret'];

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
