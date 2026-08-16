<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Http\Controllers\Controller;
use App\Models\CashLedger;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $driver = $request->user();

        return response()->json(['success' => true, 'data' => [
            'total_assigned' => $driver->assignedShipments()->whereNotIn('shipment_status', Shipment::TERMINAL_STATUSES)->count(),
            'delivered_today' => $driver->assignedShipments()->where('shipment_status', Shipment::STATUS_DELIVERED)->whereDate('delivered_at', today())->count(),
            'returned_today' => $driver->assignedShipments()->where('shipment_status', Shipment::STATUS_RETURNED)->whereDate('returned_at', today())->count(),
            'cash_in_hand' => CashLedger::balanceFor('driver', $driver->id),
        ]]);
    }
}
