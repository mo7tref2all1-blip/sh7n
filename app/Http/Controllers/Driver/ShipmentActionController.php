<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAttempt;
use App\Models\Shipment;
use App\Services\CashService;
use App\Services\ShipmentLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShipmentActionController extends Controller
{
    public function pickup(Request $request, Shipment $shipment, ShipmentLifecycleService $lifecycle): RedirectResponse
    {
        abort_unless($shipment->assigned_driver_id === $request->user()->id, 403);

        $lifecycle->pickup($shipment, $request->user(), 'driver', $request->user()->id);

        return back()->with('success', 'تم تسجيل استلام الشحنة.');
    }

    /**
     * The driver's delivery screen offers exactly these outcomes (docs/07 driver PWA):
     * تم التسليم (دفع واستلام) / رفض الاستلام / رفض الاستلام وهروب / تأجيل الاستلام / لا يرد.
     */
    public function deliver(Request $request, Shipment $shipment, ShipmentLifecycleService $lifecycle): RedirectResponse
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
            $lifecycle->deliver($shipment, $request->user(), [
                'amount_collected' => $data['amount_collected'] ?? null,
                'photo_path' => $request->hasFile('photo') ? $request->file('photo')->store('pod-photos', 'public') : null,
                'signature_path' => $request->hasFile('signature') ? $request->file('signature')->store('pod-signatures', 'public') : null,
                'notes' => $data['notes'] ?? null,
                'gps_lat' => $data['gps_lat'],
                'gps_lng' => $data['gps_lng'],
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('driver.dashboard')->with('success', "تم تسليم الشحنة {$shipment->tracking_number} بنجاح.");
    }

    /** رفض الاستلام / رفض الاستلام وهروب / تأجيل الاستلام / لا يرد — every failed-attempt outcome. */
    public function fail(Request $request, Shipment $shipment, ShipmentLifecycleService $lifecycle): RedirectResponse
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
            $lifecycle->failAttempt($shipment, $request->user(), $data['result'], [
                'notes' => $data['notes'] ?? null,
                'call_screenshot_path' => $request->hasFile('call_screenshot') ? $request->file('call_screenshot')->store('call-screenshots', 'public') : null,
                'postponed_to_date' => $data['postponed_to_date'] ?? null,
                'gps_lat' => $data['gps_lat'] ?? null,
                'gps_lng' => $data['gps_lng'] ?? null,
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $message = in_array($data['result'], DeliveryAttempt::ESCALATING_RESULTS, true)
            ? 'تم تسجيل الحالة وتصعيدها فورًا للإدارة.'
            : 'تم تسجيل نتيجة المحاولة.';

        return redirect()->route('driver.dashboard')->with('success', $message);
    }

    public function handover(Request $request, CashService $cash): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0.01']]);
        $driver = $request->user();

        $cash->initiateHandover('driver', $driver->id, 'branch', $driver->branch_id, $data['amount'], $driver);

        return back()->with('success', 'تم تسجيل توريد النقدية، بانتظار تأكيد الفرع.');
    }
}
