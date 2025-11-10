<?php

namespace App\Services;

use App\Models\{Withdraw, Currency, Account, User, TwoFactor};
use Illuminate\Support\Facades\DB;

class WithdrawalService
{
    public function createWithdrawal(User $user, string $currencyCode, $amount, string $recipientAddress, $otp = null)
    {
        return DB::transaction(function () use ($user, $currencyCode, $amount, $recipientAddress, $otp) {
            // Find currency
            $currency = Currency::where('code', $currencyCode)
                ->where('withdraw_enabled', true)
                ->firstOrFail();
            
            // Check minimum withdrawal amount
            if ($amount < $currency->min_withdraw_amount) {
                throw new \Exception('Amount below minimum withdrawal limit');
            }
            
            // Check 2FA if required
            if (config('app.withdrawal_requires_2fa', true)) {
                $this->verify2FA($user, $otp);
            }
            
            // Get account
            $account = Account::where('member_id', $user->id)
                ->where('currency_id', $currency->id)
                ->lockForUpdate()
                ->firstOrFail();
            
            // Calculate total with fee
            $fee = $currency->withdraw_fee;
            $sum = $amount + $fee;
            
            // Check balance
            if ($account->balance < $sum) {
                throw new \Exception('Insufficient balance');
            }
            
            // Create withdrawal
            $withdraw = Withdraw::create([
                'member_id' => $user->id,
                'currency_id' => $currency->id,
                'amount' => $amount,
                'fee' => $fee,
                'sum' => $sum,
                'rid' => $recipientAddress,
                'state' => Withdraw::STATE_SUBMITTED,
                'aasm_state' => Withdraw::STATE_SUBMITTED,
                'type' => 'coin',
                'tid' => $this->generateTid(),
            ]);
            
            // Submit withdrawal (locks funds)
            $withdraw->submit();
            
            return $withdraw;
        });
    }
    
    protected function verify2FA(User $user, $otp)
    {
        if (!$otp) {
            throw new \Exception('2FA code is required');
        }
        
        $twoFactor = TwoFactor::where('member_id', $user->id)
            ->where('activated', true)
            ->first();
            
        if (!$twoFactor) {
            throw new \Exception('2FA is not enabled for this account');
        }
        
        // Verify OTP (simplified - implement actual verification)
        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $valid = $google2fa->verifyKey($twoFactor->otp_secret, $otp);
        
        if (!$valid) {
            throw new \Exception('Invalid 2FA code');
        }
        
        $twoFactor->last_verify_at = now();
        $twoFactor->save();
    }
    
    protected function generateTid()
    {
        return 'TID' . strtoupper(uniqid());
    }
    
    public function processWithdrawal(Withdraw $withdraw)
    {
        // This would be called by a background job
        // to actually process the blockchain transaction
        
        DB::transaction(function () use ($withdraw) {
            $withdraw->process();
            
            // TODO: Implement actual blockchain transaction
            // For now, just mark as done
            $txid = 'txid_' . uniqid();
            $withdraw->txid = $txid;
            $withdraw->success();
        });
        
        return $withdraw;
    }
}
