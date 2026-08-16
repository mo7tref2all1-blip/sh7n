<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(Request $request): View
    {
        $wallet = $request->user()->merchant->wallet()->with('transactions')->firstOrFail();

        return view('merchant.wallet.index', compact('wallet'));
    }

    public function settlements(Request $request): View
    {
        $settlements = $request->user()->merchant->settlements()->with('attachments')->latest()->paginate(20);

        return view('merchant.settlements.index', compact('settlements'));
    }
}
