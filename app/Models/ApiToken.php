<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'access_key',
        'secret_key',
        'trusted_ip_list',
        'label',
        'expire_at',
        'scopes',
        'deleted_at',
    ];

    protected $hidden = ['secret_key'];

    protected $casts = [
        'expire_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }
}
