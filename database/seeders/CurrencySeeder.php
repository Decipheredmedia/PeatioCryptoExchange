<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrencySeeder extends Seeder
{
    public function run()
    {
        $currencies = [
            [
                'code' => 'BTC',
                'name' => 'Bitcoin',
                'symbol' => '₿',
                'coin' => true,
                'precision' => 8,
                'withdraw_fee' => '0.0005',
                'deposit_fee' => '0',
                'min_deposit_amount' => '0.001',
                'min_withdraw_amount' => '0.001',
                'quick_withdraw_limit' => '10',
                'visible' => true,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'base_factor' => 100000000,
            ],
            [
                'code' => 'ETH',
                'name' => 'Ethereum',
                'symbol' => 'Ξ',
                'coin' => true,
                'precision' => 8,
                'withdraw_fee' => '0.01',
                'deposit_fee' => '0',
                'min_deposit_amount' => '0.01',
                'min_withdraw_amount' => '0.01',
                'quick_withdraw_limit' => '100',
                'visible' => true,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'base_factor' => 1000000000000000000,
            ],
            [
                'code' => 'LTC',
                'name' => 'Litecoin',
                'symbol' => 'Ł',
                'coin' => true,
                'precision' => 8,
                'withdraw_fee' => '0.001',
                'deposit_fee' => '0',
                'min_deposit_amount' => '0.01',
                'min_withdraw_amount' => '0.01',
                'quick_withdraw_limit' => '100',
                'visible' => true,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'base_factor' => 100000000,
            ],
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'coin' => false,
                'precision' => 2,
                'withdraw_fee' => '0',
                'deposit_fee' => '0',
                'min_deposit_amount' => '10',
                'min_withdraw_amount' => '10',
                'quick_withdraw_limit' => '10000',
                'visible' => true,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'base_factor' => 1,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::create($currency);
        }
    }
}
