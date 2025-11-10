<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Process pending deposits
        $schedule->command('deposits:process')->everyMinute();
        
        // Process pending withdrawals
        $schedule->command('withdrawals:process')->everyFiveMinutes();
        
        // Update market tickers
        $schedule->command('markets:update-tickers')->everyMinute();
        
        // Clean old trade records
        $schedule->command('trades:cleanup')->daily();
        
        // Database backup
        $schedule->command('db:backup')->daily()->at('02:00');
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
