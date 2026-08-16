<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $merchant = $request->user()->merchant;

        $totalShipments = $merchant->shipments()->count();
        $delivered = $merchant->shipments()->where('shipment_status', Shipment::STATUS_DELIVERED)->count();
        $returned = $merchant->shipments()->where('shipment_status', Shipment::STATUS_RETURNED_TO_MERCHANT)->count();

        $kpis = [
            'total' => $totalShipments,
            'delivered' => $delivered,
            'returned' => $returned,
            'success_rate' => $totalShipments > 0 ? round($delivered / $totalShipments * 100, 1) : 0,
            // Collected: informational total shown instantly to the merchant — see docs/09.
            'total_collected' => $merchant->shipments()->where('financial_status', '!=', Shipment::FIN_UNCOLLECTED)->sum('amount_collected'),
            'wallet_balance' => $merchant->wallet->current_balance,
        ];

        $recentShipments = $merchant->shipments()->latest()->limit(8)->get();

        return view('merchant.dashboard', compact('merchant', 'kpis', 'recentShipments'));
    }
}
