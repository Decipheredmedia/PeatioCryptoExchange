<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TwoFactor extends Model
{
    use HasFactory;

    const TYPE_APP = 'app';
    const TYPE_SMS = 'sms';
    const TYPE_EMAIL = 'email';

    protected $fillable = [
        'member_id',
        'type',
        'otp_secret',
        'activated',
        'refreshed_at',
        'last_verify_at',
    ];

    protected $hidden = ['otp_secret'];

    protected $casts = [
        'activated' => 'boolean',
        'refreshed_at' => 'datetime',
        'last_verify_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }
}
