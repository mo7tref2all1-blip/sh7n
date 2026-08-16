@extends('layouts.app')
@section('title', 'شحناتي')

@section('content')
<div class="row g-2 mb-3">
    <div class="col-4"><div class="card stat-card p-2 text-center"><small class="text-muted">متبقي</small><h4>{{ $shipments->count() }}</h4></div></div>
    <div class="col-4"><div class="card stat-card p-2 text-center"><small class="text-muted">مسلَّم اليوم</small><h4 class="text-success">{{ $deliveredToday }}</h4></div></div>
    <div class="col-4"><div class="card stat-card p-2 text-center"><small class="text-muted">نقدية بعهدتي</small><h4>{{ number_format($cashInHand, 0) }}</h4></div></div>
</div>

<div class="card p-3 mb-3">
    <h6>توريد نقدية لنهاية اليوم</h6>
    <form method="POST" action="{{ route('driver.handovers.store') }}" class="d-flex gap-2">
        @csrf
        <input type="number" step="0.01" name="amount" class="form-control" placeholder="المبلغ" required>
        <button class="btn btn-primary">توريد للفرع</button>
    </form>
</div>

<div class="list-group">
    @forelse ($shipments as $shipment)
        <a href="{{ route('driver.shipments.show', $shipment) }}" class="list-group-item list-group-item-action">
            <div class="d-flex justify-content-between">
                <div>
                    <strong>{{ $shipment->tracking_number }}</strong>
                    <div class="small text-muted">{{ $shipment->consignee_name }} — {{ $shipment->consignee_phone }}</div>
                    <div class="small">{{ $shipment->governorate->name_ar ?? '' }}</div>
                </div>
                <div class="text-end">
                    <x-status-badge :status="$shipment->shipment_status" />
                    <div class="small mt-1">{{ number_format($shipment->amount_to_collect, 0) }} ج.م</div>
                </div>
            </div>
        </a>
    @empty
        <p class="text-muted text-center py-4">لا توجد شحنات مخصصة لك حاليًا.</p>
    @endforelse
</div>
@endsection
