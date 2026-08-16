<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\CashService;
use App\Services\ShipmentLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $shipments = $request->user()->assignedShipments()
            ->whereNotIn('shipment_status', Shipment::TERMINAL_STATUSES)
            ->with('governorate')
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $shipments]);
    }

    public function show(Request $request, Shipment $shipment): JsonResponse
    {
        abort_unless($shipment->assigned_driver_id === $request->user()->id, 403);
        $shipment->load('statusHistory', 'pod', 'governorate', 'merchant');

        return response()->json(['success' => true, 'data' => $shipment]);
    }

    public function scanPickup(Request $request, Shipment $shipment, ShipmentLifecycleService $lifecycle): JsonResponse
    {
        abort_unless($shipment->assigned_driver_id === $request->user()->id, 403);

        $shipment = $lifecycle->pickup($shipment, $request->user(), 'driver', $request->user()->id);

        return response()->json(['success' => true, 'data' => $shipment, 'message' => 'تم استلام الشحنة']);
    }

    public function deliver(Request $request, Shipment $shipment, ShipmentLifecycleService $lifecycle): JsonResponse
    {
        abort_unless($shipment->assigned_driver_id === $request->user()->id, 403);

        $data = $request->validate([
            'amount_collected' => ['nullable', 'numeric', 'min:0'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'signature' => ['nullable', 'image', 'max:2048'],
            'notes' => ['nullable', 'string'],
            'gps_lat' => ['required', 'numeric'],
            'gps_lng' => ['required', 'numeric'],
        ]);

        try {
            $shipment = $lifecycle->deliver($shipment, $request->user(), [
                'amount_collected' => $data['amount_collected'] ?? null,
                'photo_path' => $request->hasFile('photo') ? $request->file('photo')->store('pod-photos', 'public') : null,
                'signature_path' => $request->hasFile('signature') ? $request->file('signature')->store('pod-signatures', 'public') : null,
                'notes' => $data['notes'] ?? null,
                'gps_lat' => $data['gps_lat'],
                'gps_lng' => $data['gps_lng'],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }

        return response()->json(['success' => true, 'data' => $shipment, 'message' => 'تم التسليم بنجاح']);
    }

    public function failAttempt(Request $request, Shipment $shipment, ShipmentLifecycleService $lifecycle): JsonResponse
    {
        abort_unless($shipment->assigned_driver_id === $request->user()->id, 403);

        $data = $request->validate([
            'result' => ['required', 'in:no_answer,postponed,refused_receipt,refused_receipt_fled,wrong_address,refused_price'],
            'notes' => ['nullable', 'string'],
            'call_screenshot' => ['nullable', 'image', 'max:5120'],
            'postponed_to_date' => ['nullable', 'date'],
            'gps_lat' => ['nullable', 'numeric'],
            'gps_lng' => ['nullable', 'numeric'],
        ]);

        try {
            $shipment = $lifecycle->failAttempt($shipment, $request->user(), $data['result'], [
                'notes' => $data['notes'] ?? null,
                'call_screenshot_path' => $request->hasFile('call_screenshot') ? $request->file('call_screenshot')->store('call-screenshots', 'public') : null,
                'postponed_to_date' => $data['postponed_to_date'] ?? null,
                'gps_lat' => $data['gps_lat'] ?? null,
                'gps_lng' => $data['gps_lng'] ?? null,
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }

        return response()->json(['success' => true, 'data' => $shipment]);
    }

    public function handover(Request $request, CashService $cash): JsonResponse
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0.01']]);
        $driver = $request->user();

        $handover = $cash->initiateHandover('driver', $driver->id, 'branch', $driver->branch_id, $data['amount'], $driver);

        return response()->json(['success' => true, 'data' => $handover]);
    }
}
