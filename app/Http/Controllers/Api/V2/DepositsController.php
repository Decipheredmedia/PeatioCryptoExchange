<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Currency;
use App\Models\PaymentAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepositsController extends Controller
{
    public function index(Request $request)
    {
        $query = Deposit::where('member_id', Auth::id());
        
        if ($request->has('currency')) {
            $currency = Currency::where('code', $request->currency)->first();
            if ($currency) {
                $query->where('currency_id', $currency->id);
            }
        }
        
        if ($request->has('state')) {
            $query->where('state', $request->state);
        }
        
        $deposits = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 100));
            
        return response()->json($deposits);
    }
    
    public function show($id)
    {
        $deposit = Deposit::where('member_id', Auth::id())
            ->findOrFail($id);
            
        return response()->json($deposit);
    }
    
    public function depositAddress($currencyCode)
    {
        $currency = Currency::where('code', $currencyCode)
            ->where('deposit_enabled', true)
            ->firstOrFail();
            
        $address = PaymentAddress::firstOrCreate(
            [
                'member_id' => Auth::id(),
                'currency_id' => $currency->id,
            ],
            [
                'address' => $this->generateAddress($currency),
            ]
        );
        
        return response()->json([
            'currency' => $currency->code,
            'address' => $address->address,
        ]);
    }
    
    protected function generateAddress($currency)
    {
        // This is a placeholder - implement actual address generation
        // based on the cryptocurrency type
        return 'generated_' . $currency->code . '_' . uniqid();
    }
}
