<?php

namespace App\Imports;

use App\Models\Governorate;
use App\Models\Merchant;
use App\Models\ShipmentImport;
use App\Models\User;
use App\Services\ShipmentLifecycleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Row;

/**
 * Bulk shipment import (docs/05-Workflows.md § 5.6). Runs as queued, chunked jobs so a
 * multi-thousand-row file never risks a shared-hosting request timeout — each chunk is
 * its own queue job, picked up by the `queue:work --stop-when-empty` cron (docs/08).
 *
 * Template columns (row 1 headings, any case/spacing): consignee_name, consignee_phone,
 * governorate, address, description, weight_kg, amount_to_collect, reference_number.
 */
class ShipmentsImport implements OnEachRow, ShouldQueue, WithChunkReading, WithEvents, WithHeadingRow
{
    use SerializesModels;

    public function __construct(
        private readonly Merchant $merchant,
        private readonly ShipmentImport $shipmentImport,
        private readonly User $actor,
    ) {}

    public function onRow(Row $row): void
    {
        $data = $row->toArray();
        $rowNumber = $row->getIndex();

        DB::transaction(function () use ($data, $rowNumber) {
            try {
                $governorate = Governorate::where('name_ar', trim((string) ($data['governorate'] ?? '')))->first();

                if (! $governorate) {
                    throw ValidationException::withMessages(['governorate' => "المحافظة \"{$data['governorate']}\" غير معروفة"]);
                }

                app(ShipmentLifecycleService::class)->createShipment($this->merchant, [
                    'consignee_name' => $data['consignee_name'] ?? null,
                    'consignee_phone' => $data['consignee_phone'] ?? null,
                    'governorate_id' => $governorate->id,
                    'address_text' => $data['address'] ?? null,
                    'package_description' => $data['description'] ?? null,
                    'weight_kg' => $data['weight_kg'] ?? 1,
                    'service_type' => 'normal',
                    'payment_method' => 'cod',
                    'amount_to_collect' => $data['amount_to_collect'] ?? 0,
                    'reference_number' => $data['reference_number'] ?? null,
                ], $this->actor);

                $this->shipmentImport->increment('success_count');
            } catch (\Throwable $e) {
                $this->shipmentImport->increment('error_count');
                $errors = $this->shipmentImport->errors ?? [];
                $errors[] = ['row' => $rowNumber, 'message' => $e->getMessage()];
                $this->shipmentImport->update(['errors' => $errors]);
            }
        });

        $this->shipmentImport->increment('total_rows');
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function () {
                $this->shipmentImport->update(['status' => ShipmentImport::STATUS_COMPLETED, 'completed_at' => now()]);
            },
        ];
    }
}
