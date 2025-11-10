<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Deposit;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class ProcessDeposits extends Command
{
    protected $signature = 'deposits:process';
    protected $description = 'Process pending cryptocurrency deposits';

    public function handle()
    {
        $deposits = Deposit::where('state', Deposit::STATE_SUBMITTED)
            ->orWhere('state', Deposit::STATE_CHECKED)
            ->get();

        foreach ($deposits as $deposit) {
            DB::transaction(function () use ($deposit) {
                // Check confirmations (this would normally check blockchain)
                // For now, just accept after 3 confirmations
                if ($deposit->confirmations >= 3) {
                    $deposit->accept();
                    $this->info("Accepted deposit #{$deposit->id} for {$deposit->amount} {$deposit->currency->code}");
                }
            });
        }

        $this->info("Processed {$deposits->count()} deposits");
        return 0;
    }
}
