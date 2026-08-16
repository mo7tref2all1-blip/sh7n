<?php

namespace App\Http\Controllers\Api\V1\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Governorate;
use App\Services\ShipmentLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    private const RULES = [
        'consignee_name' => ['required', 'string', 'max:150'],
        'consignee_phone' => ['required', 'string', 'max:20'],
        'consignee_phone_alt' => ['nullable', 'string', 'max:20'],
        'governorate_id' => ['required', 'exists:governorates,id'],
        'zone_id' => ['nullable', 'exists:zones,id'],
        'address_text' => ['required', 'string'],
        'package_description' => ['nullable', 'string', 'max:255'],
        'weight_kg' => ['nullable', 'numeric', 'min:0.1'],
        'service_type' => ['nullable', 'in:normal,express,same_day'],
        'payment_method' => ['nullable', 'in:cod,prepaid,visa_on_delivery,bank_transfer,wallet_payment'],
        'amount_to_collect' => ['nullable', 'numeric', 'min:0'],
        'reference_number' => ['nullable', 'string', 'max:100'],
    ];

    public function index(Request $request): JsonResponse
    {
        $shipments = $request->user()->merchant->shipments()
            ->when($request->status, fn ($q) => $q->where('shipment_status', $request->status))
            ->when($request->financial_status, fn ($q) => $q->where('financial_status', $request->financial_status))
            ->latest()->paginate($request->integer('per_page', 20));

        return response()->json(['success' => true, 'data' => $shipments->items(), 'meta' => [
            'page' => $shipments->currentPage(), 'per_page' => $shipments->perPage(), 'total' => $shipments->total(),
        ]]);
    }

    public function show(Request $request, string $trackingNumber): JsonResponse
    {
        $shipment = $request->user()->merchant->shipments()->where('tracking_number', $trackingNumber)->with('statusHistory')->firstOrFail();

        return response()->json(['success' => true, 'data' => $shipment]);
    }

    public function store(Request $request, ShipmentLifecycleService $lifecycle): JsonResponse
    {
        $data = $request->validate(self::RULES);
        $shipment = $lifecycle->createShipment($request->user()->merchant, $data, $request->user());

        return response()->json(['success' => true, 'data' => $shipment], 201);
    }

    /** Bulk create — up to 500 shipments per request (docs/06-API-Design.md § 6.2). */
    public function storeBulk(Request $request, ShipmentLifecycleService $lifecycle): JsonResponse
    {
        $data = $request->validate(['shipments' => ['required', 'array', 'max:500'], 'shipments.*' => ['array']]);

        $created = [];
        $errors = [];
        foreach ($data['shipments'] as $index => $row) {
            $validator = validator($row, self::RULES);
            if ($validator->fails()) {
                $errors[] = ['index' => $index, 'errors' => $validator->errors()];

                continue;
            }
            $created[] = $lifecycle->createShipment($request->user()->merchant, $validator->validated(), $request->user());
        }

        return response()->json(['success' => true, 'data' => ['created' => $created, 'errors' => $errors]]);
    }
}
