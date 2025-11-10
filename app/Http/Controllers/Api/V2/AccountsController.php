<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountsController extends Controller
{
    public function index()
    {
        $accounts = Account::with('currency')
            ->where('member_id', Auth::id())
            ->get()
            ->map(function($account) {
                return [
                    'currency' => $account->currency->code,
                    'balance' => $account->balance,
                    'locked' => $account->locked,
                    'available' => $account->available_balance,
                ];
            });
            
        return response()->json($accounts);
    }
    
    public function show($currencyCode)
    {
        $currency = Currency::where('code', $currencyCode)->firstOrFail();
        
        $account = Account::where('member_id', Auth::id())
            ->where('currency_id', $currency->id)
            ->firstOrFail();
            
        return response()->json([
            'currency' => $account->currency->code,
            'balance' => $account->balance,
            'locked' => $account->locked,
            'available' => $account->available_balance,
        ]);
    }
}
