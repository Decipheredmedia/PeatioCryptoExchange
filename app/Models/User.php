<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Passport\HasApiTokens as PassportHasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, PassportHasApiTokens, HasRoles, SoftDeletes;

    protected $table = 'members';

    protected $fillable = [
        'email',
        'password',
        'sn',
        'display_name',
        'activated',
        'country_code',
        'phone_number',
        'disabled',
        'api_disabled',
        'nickname',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'activated' => 'boolean',
        'disabled' => 'boolean',
        'api_disabled' => 'boolean',
        'password' => 'hashed',
    ];

    public function accounts()
    {
        return $this->hasMany(Account::class, 'member_id');
    }

    public function identities()
    {
        return $this->hasMany(Identity::class, 'member_id');
    }

    public function twoFactors()
    {
        return $this->hasMany(TwoFactor::class, 'member_id');
    }

    public function deposits()
    {
        return $this->hasMany(Deposit::class, 'member_id');
    }

    public function withdraws()
    {
        return $this->hasMany(Withdraw::class, 'member_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'member_id');
    }

    public function trades()
    {
        return $this->hasMany(Trade::class, 'member_id');
    }

    public function paymentAddresses()
    {
        return $this->hasMany(PaymentAddress::class, 'member_id');
    }

    public function apiTokens()
    {
        return $this->hasMany(ApiToken::class, 'member_id');
    }

    public function idDocuments()
    {
        return $this->hasMany(IdDocument::class, 'member_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'author_id');
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function hasActivated()
    {
        return $this->activated;
    }

    public function isDisabled()
    {
        return $this->disabled;
    }
}
