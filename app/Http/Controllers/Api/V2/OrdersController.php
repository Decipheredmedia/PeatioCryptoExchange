<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Market;
use App\Services\TradingEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrdersController extends Controller
{
    protected $tradingEngine;
    
    public function __construct(TradingEngine $tradingEngine)
    {
        $this->tradingEngine = $tradingEngine;
    }
    
    public function index(Request $request)
    {
        $query = Order::where('member_id', Auth::id());
        
        if ($request->has('market')) {
            $market = Market::where('ask_unit', $request->market)
                ->orWhere('bid_unit', $request->market)
                ->first();
            if ($market) {
                $query->where('market_id', $market->id);
            }
        }
        
        if ($request->has('state')) {
            $query->where('state', $request->state);
        } else {
            $query->where('state', 'wait');
        }
        
        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 100));
            
        return response()->json($orders);
    }
    
    public function show($id)
    {
        $order = Order::where('member_id', Auth::id())
            ->findOrFail($id);
            
        return response()->json($order);
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'market' => 'required|string',
            'side' => 'required|in:buy,sell',
            'volume' => 'required|numeric|min:0.00000001',
            'price' => 'nullable|numeric|min:0',
            'ord_type' => 'required|in:limit,market',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $order = $this->tradingEngine->createOrder(
                Auth::user(),
                $request->market,
                $request->side,
                $request->volume,
                $request->price,
                $request->ord_type
            );
            
            return response()->json($order, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    
    public function destroy($id)
    {
        $order = Order::where('member_id', Auth::id())
            ->where('state', 'wait')
            ->findOrFail($id);
            
        try {
            $this->tradingEngine->cancelOrder($order);
            return response()->json(['message' => 'Order cancelled successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    
    public function destroyAll(Request $request)
    {
        $query = Order::where('member_id', Auth::id())
            ->where('state', 'wait');
            
        if ($request->has('side')) {
            $query->where('side', $request->side);
        }
        
        $orders = $query->get();
        
        foreach ($orders as $order) {
            try {
                $this->tradingEngine->cancelOrder($order);
            } catch (\Exception $e) {
                // Continue cancelling other orders
            }
        }
        
        return response()->json(['message' => 'Orders cancelled successfully']);
    }
}
