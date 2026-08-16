<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\CashLedger;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $driver = $request->user();

        $shipments = $driver->assignedShipments()->whereNotIn('shipment_status', Shipment::TERMINAL_STATUSES)->latest()->get();
        $deliveredToday = $driver->assignedShipments()->where('shipment_status', Shipment::STATUS_DELIVERED)->whereDate('delivered_at', today())->count();
        $returnedToday = $driver->assignedShipments()->where('shipment_status', Shipment::STATUS_RETURNED)->whereDate('returned_at', today())->count();
        $cashInHand = CashLedger::balanceFor('driver', $driver->id);

        return view('driver.dashboard', compact('shipments', 'deliveredToday', 'returnedToday', 'cashInHand'));
    }

    public function show(Request $request, Shipment $shipment): View
    {
        abort_unless($shipment->assigned_driver_id === $request->user()->id, 403);

        return view('driver.shipment', compact('shipment'));
    }
}
