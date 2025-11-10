<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'currency_id',
        'balance',
        'locked',
        'in',
        'out',
    ];

    protected $casts = [
        'balance' => 'decimal:16',
        'locked' => 'decimal:16',
        'in' => 'decimal:16',
        'out' => 'decimal:16',
    ];

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function versions()
    {
        return $this->hasMany(AccountVersion::class);
    }

    public function getAvailableBalanceAttribute()
    {
        return $this->balance - $this->locked;
    }

    public function lock($amount)
    {
        $this->locked += $amount;
        $this->balance -= $amount;
        $this->save();
    }

    public function unlock($amount)
    {
        $this->locked -= $amount;
        $this->balance += $amount;
        $this->save();
    }

    public function credit($amount, $reason = null, $modifiable = null)
    {
        $this->balance += $amount;
        $this->in += $amount;
        $this->save();

        $this->recordVersion($amount, $reason, $modifiable);
    }

    public function debit($amount, $reason = null, $modifiable = null)
    {
        $this->balance -= $amount;
        $this->out += $amount;
        $this->save();

        $this->recordVersion(-$amount, $reason, $modifiable);
    }

    protected function recordVersion($amount, $reason = null, $modifiable = null)
    {
        AccountVersion::create([
            'member_id' => $this->member_id,
            'account_id' => $this->id,
            'reason' => $reason,
            'balance' => $this->balance,
            'locked' => $this->locked,
            'amount' => $amount,
            'modifiable_id' => $modifiable ? $modifiable->id : null,
            'modifiable_type' => $modifiable ? get_class($modifiable) : null,
            'currency_id' => $this->currency_id,
        ]);
    }
}
