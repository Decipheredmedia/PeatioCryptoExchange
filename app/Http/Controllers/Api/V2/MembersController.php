<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MembersController extends Controller
{
    public function me()
    {
        $user = Auth::user();
        
        return response()->json([
            'sn' => $user->sn,
            'email' => $user->email,
            'activated' => $user->activated,
            'accounts' => $user->accounts->map(function($account) {
                return [
                    'currency' => $account->currency->code,
                    'balance' => $account->balance,
                    'locked' => $account->locked,
                ];
            }),
        ]);
    }
}
