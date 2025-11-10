<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Withdraw;
use App\Services\WithdrawalService;

class ProcessWithdrawals extends Command
{
    protected $signature = 'withdrawals:process';
    protected $description = 'Process pending cryptocurrency withdrawals';

    protected $withdrawalService;

    public function __construct(WithdrawalService $withdrawalService)
    {
        parent::__construct();
        $this->withdrawalService = $withdrawalService;
    }

    public function handle()
    {
        $withdrawals = Withdraw::where('state', Withdraw::STATE_ACCEPTED)
            ->orWhere('state', Withdraw::STATE_PROCESSING)
            ->get();

        foreach ($withdrawals as $withdrawal) {
            try {
                $this->withdrawalService->processWithdrawal($withdrawal);
                $this->info("Processed withdrawal #{$withdrawal->id} for {$withdrawal->amount} {$withdrawal->currency->code}");
            } catch (\Exception $e) {
                $this->error("Failed to process withdrawal #{$withdrawal->id}: " . $e->getMessage());
            }
        }

        $this->info("Processed {$withdrawals->count()} withdrawals");
        return 0;
    }
}
