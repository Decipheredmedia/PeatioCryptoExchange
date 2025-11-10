<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Market;
use App\Models\Currency;

class MarketSeeder extends Seeder
{
    public function run()
    {
        $btc = Currency::where('code', 'BTC')->first();
        $eth = Currency::where('code', 'ETH')->first();
        $ltc = Currency::where('code', 'LTC')->first();
        $usd = Currency::where('code', 'USD')->first();

        $markets = [
            [
                'ask_currency_id' => $btc->id,
                'bid_currency_id' => $usd->id,
                'ask_unit' => 'btc',
                'bid_unit' => 'usd',
                'ask_fee' => '0.002',
                'bid_fee' => '0.002',
                'min_ask_price' => '0.01',
                'max_bid_price' => '1000000',
                'min_ask_amount' => '0.001',
                'min_bid_amount' => '10',
                'ask_precision' => 8,
                'bid_precision' => 2,
                'enabled' => true,
                'visible' => true,
            ],
            [
                'ask_currency_id' => $eth->id,
                'bid_currency_id' => $usd->id,
                'ask_unit' => 'eth',
                'bid_unit' => 'usd',
                'ask_fee' => '0.002',
                'bid_fee' => '0.002',
                'min_ask_price' => '0.01',
                'max_bid_price' => '100000',
                'min_ask_amount' => '0.01',
                'min_bid_amount' => '10',
                'ask_precision' => 8,
                'bid_precision' => 2,
                'enabled' => true,
                'visible' => true,
            ],
            [
                'ask_currency_id' => $ltc->id,
                'bid_currency_id' => $usd->id,
                'ask_unit' => 'ltc',
                'bid_unit' => 'usd',
                'ask_fee' => '0.002',
                'bid_fee' => '0.002',
                'min_ask_price' => '0.01',
                'max_bid_price' => '10000',
                'min_ask_amount' => '0.1',
                'min_bid_amount' => '10',
                'ask_precision' => 8,
                'bid_precision' => 2,
                'enabled' => true,
                'visible' => true,
            ],
            [
                'ask_currency_id' => $eth->id,
                'bid_currency_id' => $btc->id,
                'ask_unit' => 'eth',
                'bid_unit' => 'btc',
                'ask_fee' => '0.002',
                'bid_fee' => '0.002',
                'min_ask_price' => '0.00001',
                'max_bid_price' => '1',
                'min_ask_amount' => '0.01',
                'min_bid_amount' => '0.001',
                'ask_precision' => 8,
                'bid_precision' => 8,
                'enabled' => true,
                'visible' => true,
            ],
        ];

        foreach ($markets as $market) {
            Market::create($market);
        }
    }
}
