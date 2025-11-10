<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Models\Currency;
use App\Models\Trade;
use Illuminate\Http\Request;

class MarketsController extends Controller
{
    public function index()
    {
        $markets = Market::with(['askCurrency', 'bidCurrency'])
            ->where('visible', true)
            ->where('enabled', true)
            ->get();
            
        return response()->json($markets);
    }
    
    public function show($id)
    {
        $market = Market::with(['askCurrency', 'bidCurrency'])->findOrFail($id);
        return response()->json($market);
    }
    
    public function tickers($id)
    {
        $market = Market::findOrFail($id);
        
        $trades = Trade::where('market_id', $market->id)
            ->orderBy('created_at', 'desc')
            ->limit(1000)
            ->get();
            
        $ticker = [
            'at' => time(),
            'ticker' => [
                'buy' => $trades->where('trend', 1)->avg('price') ?? 0,
                'sell' => $trades->where('trend', -1)->avg('price') ?? 0,
                'low' => $trades->min('price') ?? 0,
                'high' => $trades->max('price') ?? 0,
                'last' => $trades->first()->price ?? 0,
                'vol' => $trades->sum('volume') ?? 0,
            ]
        ];
        
        return response()->json($ticker);
    }
    
    public function depth($id)
    {
        $market = Market::findOrFail($id);
        
        $asks = \App\Models\Order::where('market_id', $market->id)
            ->where('side', 'sell')
            ->where('state', 'wait')
            ->orderBy('price', 'asc')
            ->limit(20)
            ->get()
            ->map(fn($o) => [$o->price, $o->volume]);
            
        $bids = \App\Models\Order::where('market_id', $market->id)
            ->where('side', 'buy')
            ->where('state', 'wait')
            ->orderBy('price', 'desc')
            ->limit(20)
            ->get()
            ->map(fn($o) => [$o->price, $o->volume]);
        
        return response()->json([
            'timestamp' => time(),
            'asks' => $asks,
            'bids' => $bids,
        ]);
    }
    
    public function trades($id)
    {
        $market = Market::findOrFail($id);
        
        $trades = Trade::where('market_id', $market->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
            
        return response()->json($trades);
    }
    
    public function currencies()
    {
        $currencies = Currency::where('visible', true)->get();
        return response()->json($currencies);
    }
}
