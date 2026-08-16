<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;

/** Generates the QR + Code128 barcode images printed on the shipment's AWB label. */
class BarcodeService
{
    public function generate(string $trackingNumber): void
    {
        $qrResult = (new Builder)->build(
            writer: new PngWriter,
            data: $trackingNumber,
            size: 300,
            margin: 10,
        );
        Storage::disk('public')->put("qrcodes/{$trackingNumber}.png", $qrResult->getString());

        $barcodeGenerator = new BarcodeGeneratorPNG;
        $barcodePng = $barcodeGenerator->getBarcode($trackingNumber, $barcodeGenerator::TYPE_CODE_128, 3, 80);
        Storage::disk('public')->put("barcodes/{$trackingNumber}.png", $barcodePng);
    }

    public function qrUrl(string $trackingNumber): string
    {
        return Storage::disk('public')->url("qrcodes/{$trackingNumber}.png");
    }

    public function barcodeUrl(string $trackingNumber): string
    {
        return Storage::disk('public')->url("barcodes/{$trackingNumber}.png");
    }
}
