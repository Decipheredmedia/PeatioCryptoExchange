<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Identity extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'email',
        'password_digest',
        'is_active',
        'retry_count',
        'locked_at',
    ];

    protected $hidden = ['password_digest'];

    protected $casts = [
        'is_active' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }
}
