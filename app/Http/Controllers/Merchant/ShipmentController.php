<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Imports\ShipmentsImport;
use App\Models\Governorate;
use App\Models\Shipment;
use App\Models\ShipmentImport;
use App\Models\Zone;
use App\Services\BarcodeService;
use App\Services\ShipmentLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ShipmentController extends Controller
{
    public function index(Request $request): View
    {
        $merchant = $request->user()->merchant;

        $shipments = $merchant->shipments()
            ->when($request->status, fn ($q) => $q->where('shipment_status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('merchant.shipments.index', compact('shipments'));
    }

    public function create(): View
    {
        $governorates = Governorate::orderBy('name_ar')->get();

        return view('merchant.shipments.create', compact('governorates'));
    }

    public function store(Request $request, ShipmentLifecycleService $lifecycle): RedirectResponse
    {
        $data = $request->validate([
            'consignee_name' => ['required', 'string', 'max:150'],
            'consignee_phone' => ['required', 'string', 'max:20'],
            'consignee_phone_alt' => ['nullable', 'string', 'max:20'],
            'governorate_id' => ['required', 'exists:governorates,id'],
            'zone_id' => ['nullable', 'exists:zones,id'],
            'address_text' => ['required', 'string'],
            'package_description' => ['nullable', 'string', 'max:255'],
            'weight_kg' => ['nullable', 'numeric', 'min:0.1'],
            'service_type' => ['required', 'in:normal,express,same_day'],
            'payment_method' => ['required', 'in:cod,prepaid,visa_on_delivery,bank_transfer,wallet_payment'],
            'amount_to_collect' => ['nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $shipment = $lifecycle->createShipment($request->user()->merchant, $data, $request->user());

        return redirect()->route('merchant.shipments.show', $shipment)->with('success', "تم إنشاء الشحنة {$shipment->tracking_number} بنجاح.");
    }

    public function show(Request $request, Shipment $shipment, BarcodeService $barcode): View
    {
        abort_unless($shipment->merchant_id === $request->user()->merchant_id, 403);
        $shipment->load(['statusHistory', 'pod', 'governorate']);

        return view('merchant.shipments.show', [
            'shipment' => $shipment,
            'qrUrl' => $barcode->qrUrl($shipment->tracking_number),
            'barcodeUrl' => $barcode->barcodeUrl($shipment->tracking_number),
        ]);
    }

    public function showImport(Request $request): View
    {
        $imports = ShipmentImport::where('merchant_id', $request->user()->merchant_id)->latest()->limit(10)->get();

        return view('merchant.shipments.import', compact('imports'));
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $merchant = $request->user()->merchant;
        $path = $request->file('file')->store('imports');

        $shipmentImport = ShipmentImport::create([
            'merchant_id' => $merchant->id,
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'status' => ShipmentImport::STATUS_PROCESSING,
            'created_by' => $request->user()->id,
        ]);

        Excel::import(new ShipmentsImport($merchant, $shipmentImport, $request->user()), $path);

        return redirect()->route('merchant.shipments.import.show')->with('success', 'جاري معالجة الملف في الخلفية — سيظهر تقرير النتائج بالأسفل عند الانتهاء.');
    }
}
