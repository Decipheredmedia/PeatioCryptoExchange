<?php

namespace App\Services;

use App\Models\{Order, Market, Account, Trade, User};
use Illuminate\Support\Facades\DB;

class TradingEngine
{
    public function createOrder(User $user, string $marketCode, string $side, $volume, $price = null, $ordType = 'limit')
    {
        return DB::transaction(function () use ($user, $marketCode, $side, $volume, $price, $ordType) {
            // Find market
            $market = Market::where(function($q) use ($marketCode) {
                $q->where('ask_unit', $marketCode)
                  ->orWhere('bid_unit', $marketCode);
            })->where('enabled', true)->firstOrFail();
            
            // Validate order type
            if ($ordType === 'limit' && !$price) {
                throw new \Exception('Price is required for limit orders');
            }
            
            if ($ordType === 'market') {
                $price = null; // Market orders don't have a price
            }
            
            // Determine which currency to lock
            $currencyId = $side === 'buy' ? $market->bid_currency_id : $market->ask_currency_id;
            $account = Account::where('member_id', $user->id)
                ->where('currency_id', $currencyId)
                ->lockForUpdate()
                ->firstOrFail();
            
            // Calculate locked amount
            $locked = $side === 'buy' ? ($price * $volume) : $volume;
            
            // Check balance
            if ($account->balance < $locked) {
                throw new \Exception('Insufficient balance');
            }
            
            // Lock funds
            $account->lock($locked);
            
            // Create order
            $order = Order::create([
                'member_id' => $user->id,
                'market_id' => $market->id,
                'price' => $price,
                'volume' => $volume,
                'origin_volume' => $volume,
                'locked' => $locked,
                'origin_locked' => $locked,
                'state' => 'wait',
                'type' => $ordType,
                'side' => $side,
                'trades_count' => 0,
            ]);
            
            // Try to match immediately
            $this->matchOrder($order);
            
            return $order->fresh();
        });
    }
    
    public function cancelOrder(Order $order)
    {
        return DB::transaction(function () use ($order) {
            if ($order->state !== 'wait') {
                throw new \Exception('Order cannot be cancelled');
            }
            
            // Unlock funds
            $currencyId = $order->isBid() 
                ? $order->market->bid_currency_id 
                : $order->market->ask_currency_id;
                
            $account = Account::where('member_id', $order->member_id)
                ->where('currency_id', $currencyId)
                ->lockForUpdate()
                ->first();
                
            if ($account) {
                $account->unlock($order->locked);
            }
            
            $order->state = 'cancel';
            $order->save();
            
            return $order;
        });
    }
    
    protected function matchOrder(Order $order)
    {
        $oppositeOrders = Order::where('market_id', $order->market_id)
            ->where('side', $order->side === 'buy' ? 'sell' : 'buy')
            ->where('state', 'wait')
            ->when($order->isLimit(), function($q) use ($order) {
                if ($order->isBid()) {
                    return $q->where('price', '<=', $order->price);
                } else {
                    return $q->where('price', '>=', $order->price);
                }
            })
            ->orderBy('price', $order->isBid() ? 'asc' : 'desc')
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();
            
        foreach ($oppositeOrders as $oppositeOrder) {
            if ($order->volume <= 0) {
                break;
            }
            
            $this->executeTrade($order, $oppositeOrder);
        }
    }
    
    protected function executeTrade(Order $takerOrder, Order $makerOrder)
    {
        DB::transaction(function () use ($takerOrder, $makerOrder) {
            $price = $makerOrder->price;
            $volume = min($takerOrder->volume, $makerOrder->volume);
            $funds = $price * $volume;
            
            // Create trade record
            $trade = Trade::create([
                'price' => $price,
                'volume' => $volume,
                'funds' => $funds,
                'market_id' => $takerOrder->market_id,
                'maker_id' => $makerOrder->member_id,
                'maker_order_id' => $makerOrder->id,
                'taker_id' => $takerOrder->member_id,
                'taker_order_id' => $takerOrder->id,
                'trend' => $takerOrder->isBid() ? 1 : -1,
            ]);
            
            // Update orders
            $takerOrder->strike($trade);
            $makerOrder->strike($trade);
            
            // Transfer funds
            $this->transferFunds($takerOrder, $makerOrder, $volume, $price);
        });
    }
    
    protected function transferFunds(Order $takerOrder, Order $makerOrder, $volume, $price)
    {
        $market = $takerOrder->market;
        $funds = $price * $volume;
        
        // Get accounts
        $takerBidAccount = Account::where('member_id', $takerOrder->member_id)
            ->where('currency_id', $market->bid_currency_id)->first();
        $takerAskAccount = Account::where('member_id', $takerOrder->member_id)
            ->where('currency_id', $market->ask_currency_id)->first();
        $makerBidAccount = Account::where('member_id', $makerOrder->member_id)
            ->where('currency_id', $market->bid_currency_id)->first();
        $makerAskAccount = Account::where('member_id', $makerOrder->member_id)
            ->where('currency_id', $market->ask_currency_id)->first();
        
        if ($takerOrder->isBid()) {
            // Taker buys, maker sells
            $takerBidAccount->locked -= $funds;
            $takerBidAccount->save();
            $takerAskAccount->credit($volume, 'trade');
            
            $makerAskAccount->locked -= $volume;
            $makerAskAccount->save();
            $makerBidAccount->credit($funds, 'trade');
        } else {
            // Taker sells, maker buys
            $takerAskAccount->locked -= $volume;
            $takerAskAccount->save();
            $takerBidAccount->credit($funds, 'trade');
            
            $makerBidAccount->locked -= $funds;
            $makerBidAccount->save();
            $makerAskAccount->credit($volume, 'trade');
        }
    }
}
