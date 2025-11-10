<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Trade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TradesController extends Controller
{
    public function index(Request $request)
    {
        $query = Trade::query();
        
        if ($request->has('market')) {
            $query->whereHas('market', function($q) use ($request) {
                $q->where('ask_unit', $request->market)
                  ->orWhere('bid_unit', $request->market);
            });
        }
        
        $trades = $query->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 50))
            ->get();
            
        return response()->json($trades);
    }
    
    public function my(Request $request)
    {
        $query = Trade::where(function($q) {
            $q->where('maker_id', Auth::id())
              ->orWhere('taker_id', Auth::id());
        });
        
        if ($request->has('market')) {
            $query->whereHas('market', function($q) use ($request) {
                $q->where('ask_unit', $request->market)
                  ->orWhere('bid_unit', $request->market);
            });
        }
        
        $trades = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 100));
            
        return response()->json($trades);
    }
}
