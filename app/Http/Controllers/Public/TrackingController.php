<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingController extends Controller
{
    public function show(string $trackingNumber): View
    {
        $shipment = Shipment::with('statusHistory')->where('tracking_number', $trackingNumber)->firstOrFail();

        return view('public.track', compact('shipment'));
    }

    /** Same lookup, JSON shape — used by the public API (docs/06-API-Design.md § 6.3). */
    public function apiShow(string $trackingNumber): JsonResponse
    {
        $shipment = Shipment::with('statusHistory')->where('tracking_number', $trackingNumber)->firstOrFail();

        return response()->json(['success' => true, 'data' => [
            'tracking_number' => $shipment->tracking_number,
            'shipment_status' => $shipment->shipment_status,
            'timeline' => $shipment->statusHistory->map(fn ($h) => [
                'status' => $h->status, 'created_at' => $h->created_at,
            ]),
        ]]);
    }

    public function reschedule(Request $request, string $trackingNumber): RedirectResponse
    {
        $data = $request->validate(['new_date' => ['required', 'date', 'after:today']]);
        $shipment = Shipment::where('tracking_number', $trackingNumber)->firstOrFail();

        if (! in_array($shipment->shipment_status, [Shipment::STATUS_OUT_FOR_DELIVERY, Shipment::STATUS_NO_ANSWER, Shipment::STATUS_POSTPONED], true)) {
            return back()->withErrors(['new_date' => 'لا يمكن إعادة جدولة هذه الشحنة في حالتها الحالية.']);
        }

        $shipment->update(['scheduled_delivery_date' => $data['new_date'], 'shipment_status' => Shipment::STATUS_POSTPONED]);
        $shipment->statusHistory()->create(['status' => Shipment::STATUS_POSTPONED, 'reason' => 'طلب العميل إعادة الجدولة عبر صفحة التتبع العامة', 'created_at' => now()]);

        return back()->with('success', 'تم تسجيل طلب إعادة الجدولة بنجاح.');
    }
}
