<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CashLedger;
use App\Models\Merchant;
use App\Models\Shipment;
use App\Models\Wallet;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = today();

        $kpis = [
            'today_count' => Shipment::whereDate('created_at', $today)->count(),
            'delivered_today' => Shipment::where('shipment_status', Shipment::STATUS_DELIVERED)->whereDate('delivered_at', $today)->count(),
            'returns_today' => Shipment::where('shipment_status', Shipment::STATUS_RETURNED_TO_MERCHANT)->whereDate('returned_at', $today)->count(),
            'collections_today' => Shipment::whereDate('delivered_at', $today)->where('collection_required', true)->sum('amount_collected'),
            'merchant_dues' => Wallet::sum('current_balance'),
            'driver_cash_in_hand' => $this->currentCashInHandTotal('driver'),
        ];

        $topMerchants = Merchant::withCount(['shipments as delivered_count' => fn ($q) => $q->where('shipment_status', Shipment::STATUS_DELIVERED)])
            ->orderByDesc('delivered_count')->limit(5)->get();

        $recentActivity = ActivityLog::with('actor')->latest('created_at')->limit(10)->get();

        return view('admin.dashboard', compact('kpis', 'topMerchants', 'recentActivity'));
    }

    /** Sum of every holder's latest ledger balance (their current cash-in-hand). */
    private function currentCashInHandTotal(string $holderType): float
    {
        $lastIdsPerHolder = CashLedger::where('holder_type', $holderType)
            ->selectRaw('MAX(id) as last_id')
            ->groupBy('holder_id')
            ->pluck('last_id');

        return (float) CashLedger::whereIn('id', $lastIdsPerHolder)->sum('balance_after');
    }
}
