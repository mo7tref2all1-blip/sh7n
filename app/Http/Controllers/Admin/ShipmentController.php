<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ShipmentLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    public function index(Request $request): View
    {
        $shipments = Shipment::with(['merchant', 'governorate'])
            ->when($request->status, fn ($q) => $q->where('shipment_status', $request->status))
            ->when($request->merchant_id, fn ($q) => $q->where('merchant_id', $request->merchant_id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $drivers = User::where('user_type', User::TYPE_DRIVER)->orderBy('name')->get();
        $agents = Agent::orderBy('name')->get();

        return view('admin.shipments.index', compact('shipments', 'drivers', 'agents'));
    }

    public function show(Shipment $shipment): View
    {
        $shipment->load(['merchant', 'governorate', 'zone', 'statusHistory.changedBy', 'custodyLog', 'pod', 'deliveryAttempts', 'assignedDriver', 'assignedAgent']);
        $drivers = User::where('user_type', User::TYPE_DRIVER)->orderBy('name')->get();
        $agents = Agent::orderBy('name')->get();

        return view('admin.shipments.show', compact('shipment', 'drivers', 'agents'));
    }

    public function assign(Request $request, Shipment $shipment, ShipmentLifecycleService $lifecycle): RedirectResponse
    {
        $data = $request->validate([
            'driver_id' => ['nullable', 'exists:users,id'],
            'agent_id' => ['nullable', 'exists:agents,id'],
        ]);

        $driver = $data['driver_id'] ?? null ? User::find($data['driver_id']) : null;
        $agent = $data['agent_id'] ?? null ? Agent::find($data['agent_id']) : null;

        $lifecycle->assign($shipment, $driver, $agent, $request->user());

        return back()->with('success', 'تم تعيين الشحنة بنجاح.');
    }
}
