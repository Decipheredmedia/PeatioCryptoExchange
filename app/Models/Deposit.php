<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    use HasFactory;

    const STATE_SUBMITTED = 'submitted';
    const STATE_ACCEPTED = 'accepted';
    const STATE_CHECKED = 'checked';
    const STATE_WARNING = 'warning';
    const STATE_REJECTED = 'rejected';
    const STATE_CANCELED = 'canceled';

    protected $fillable = [
        'member_id',
        'currency_id',
        'amount',
        'fee',
        'txid',
        'confirmations',
        'state',
        'aasm_state',
        'type',
        'tid',
        'payment_transaction_id',
    ];

    protected $casts = [
        'amount' => 'decimal:16',
        'fee' => 'decimal:16',
        'confirmations' => 'integer',
    ];

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function accept()
    {
        if ($this->state !== self::STATE_SUBMITTED) {
            return false;
        }

        $this->state = self::STATE_ACCEPTED;
        $this->aasm_state = self::STATE_ACCEPTED;
        $this->save();

        // Credit the account
        $account = Account::where('member_id', $this->member_id)
                         ->where('currency_id', $this->currency_id)
                         ->first();
        
        if ($account) {
            $account->credit($this->amount - $this->fee, 'deposit', $this);
        }

        return true;
    }

    public function reject()
    {
        $this->state = self::STATE_REJECTED;
        $this->aasm_state = self::STATE_REJECTED;
        $this->save();
    }
}
