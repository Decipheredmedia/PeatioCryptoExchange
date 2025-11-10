<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdraw extends Model
{
    use HasFactory;

    const STATE_SUBMITTED = 'submitted';
    const STATE_ACCEPTED = 'accepted';
    const STATE_SUSPECT = 'suspect';
    const STATE_REJECTED = 'rejected';
    const STATE_PROCESSING = 'processing';
    const STATE_DONE = 'done';
    const STATE_CANCELED = 'canceled';
    const STATE_FAILED = 'failed';

    protected $fillable = [
        'member_id',
        'currency_id',
        'amount',
        'fee',
        'fund_uid',
        'fund_extra',
        'txid',
        'state',
        'aasm_state',
        'sum',
        'type',
        'tid',
        'rid',
        'confirmations',
    ];

    protected $casts = [
        'amount' => 'decimal:16',
        'fee' => 'decimal:16',
        'sum' => 'decimal:16',
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

    public function submit()
    {
        $account = Account::where('member_id', $this->member_id)
                         ->where('currency_id', $this->currency_id)
                         ->first();
        
        if (!$account || $account->balance < $this->sum) {
            return false;
        }

        // Lock funds
        $account->lock($this->sum);
        
        $this->state = self::STATE_SUBMITTED;
        $this->aasm_state = self::STATE_SUBMITTED;
        $this->save();

        return true;
    }

    public function accept()
    {
        $this->state = self::STATE_ACCEPTED;
        $this->aasm_state = self::STATE_ACCEPTED;
        $this->save();
    }

    public function process()
    {
        $this->state = self::STATE_PROCESSING;
        $this->aasm_state = self::STATE_PROCESSING;
        $this->save();
    }

    public function success()
    {
        $account = Account::where('member_id', $this->member_id)
                         ->where('currency_id', $this->currency_id)
                         ->first();
        
        if ($account) {
            $account->locked -= $this->sum;
            $account->out += $this->sum;
            $account->save();
        }

        $this->state = self::STATE_DONE;
        $this->aasm_state = self::STATE_DONE;
        $this->save();
    }

    public function reject()
    {
        $account = Account::where('member_id', $this->member_id)
                         ->where('currency_id', $this->currency_id)
                         ->first();
        
        if ($account) {
            $account->unlock($this->sum);
        }

        $this->state = self::STATE_REJECTED;
        $this->aasm_state = self::STATE_REJECTED;
        $this->save();
    }
}
