<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Withdraw;
use App\Models\Currency;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WithdrawsController extends Controller
{
    protected $withdrawalService;
    
    public function __construct(WithdrawalService $withdrawalService)
    {
        $this->withdrawalService = $withdrawalService;
    }
    
    public function index(Request $request)
    {
        $query = Withdraw::where('member_id', Auth::id());
        
        if ($request->has('currency')) {
            $currency = Currency::where('code', $request->currency)->first();
            if ($currency) {
                $query->where('currency_id', $currency->id);
            }
        }
        
        if ($request->has('state')) {
            $query->where('state', $request->state);
        }
        
        $withdraws = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 100));
            
        return response()->json($withdraws);
    }
    
    public function show($id)
    {
        $withdraw = Withdraw::where('member_id', Auth::id())
            ->findOrFail($id);
            
        return response()->json($withdraw);
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'rid' => 'required|string', // Recipient address
            'otp' => 'nullable|string', // 2FA code if required
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $withdraw = $this->withdrawalService->createWithdrawal(
                Auth::user(),
                $request->currency,
                $request->amount,
                $request->rid,
                $request->otp
            );
            
            return response()->json($withdraw, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
