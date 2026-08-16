<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\CashLedger;
use App\Models\Shipment;
use App\Services\CashService;
use App\Services\ShipmentLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $agent = $request->user()->agent;

        $shipments = $agent->shipments()->with('assignedDriver')->latest()->limit(50)->get();
        $drivers = $agent->drivers()->withCount([
            'assignedShipments as delivered_count' => fn ($q) => $q->where('shipment_status', Shipment::STATUS_DELIVERED),
            'assignedShipments as total_count',
        ])->get();

        $cashInHand = CashLedger::balanceFor('agent', $agent->id);

        return view('agent.dashboard', compact('agent', 'shipments', 'drivers', 'cashInHand'));
    }

    public function assignDriver(Request $request, Shipment $shipment, ShipmentLifecycleService $lifecycle): RedirectResponse
    {
        abort_unless($shipment->assigned_agent_id === $request->user()->agent_id, 403);

        $data = $request->validate(['driver_id' => ['required', 'exists:users,id']]);
        $driver = $request->user()->agent->drivers()->findOrFail($data['driver_id']);

        $lifecycle->assign($shipment, $driver, $request->user()->agent, $request->user());

        return back()->with('success', 'تم توزيع الشحنة على المندوب.');
    }

    public function handover(Request $request, CashService $cash): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0.01']]);
        $agent = $request->user()->agent;

        $cash->initiateHandover('agent', $agent->id, 'company', null, $data['amount'], $request->user());

        return back()->with('success', 'تم تسجيل طلب توريد النقدية، بانتظار تأكيد المحاسب.');
    }
}
