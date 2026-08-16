@extends('layouts.app')
@section('title', $shipment->tracking_number)
@section('sidebar')@include('partials.sidebar-merchant')@endsection

@section('content')
<div class="row g-3">
    <div class="col-md-8">
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between">
                <div>
                    <h5>{{ $shipment->tracking_number }}</h5>
                    <p class="mb-1">{{ $shipment->consignee_name }} — {{ $shipment->consignee_phone }}</p>
                    <p class="text-muted mb-0">{{ $shipment->governorate->name_ar }} — {{ $shipment->address_text }}</p>
                </div>
                <div class="text-end">
                    <x-status-badge :status="$shipment->shipment_status" /><br>
                    <span class="d-inline-block mt-1"><x-financial-badge :status="$shipment->financial_status" /></span>
                </div>
            </div>
        </div>
        <div class="card p-3">
            <h6>سجل التتبع</h6>
            <ul class="list-group list-group-flush">
                @foreach ($shipment->statusHistory as $h)
                    <li class="list-group-item d-flex justify-content-between">
                        <x-status-badge :status="$h->status" />
                        <span class="text-muted small">{{ $h->created_at->format('Y-m-d H:i') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 text-center">
            <h6>بوليصة الشحن (AWB)</h6>
            <img src="{{ $qrUrl }}" alt="QR" style="width:150px;margin:auto">
            <img src="{{ $barcodeUrl }}" alt="Barcode" style="width:100%;margin-top:10px">
            <p class="small text-muted mt-2">{{ $shipment->tracking_number }}</p>
        </div>
    </div>
</div>
@endsection
