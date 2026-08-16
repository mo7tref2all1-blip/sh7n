@extends('layouts.app')
@section('title', 'بورتال الوكيل')
@section('sidebar')@include('partials.sidebar-agent')@endsection

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card stat-card p-3"><small class="text-muted">شحنات بعهدتي</small><h3>{{ $shipments->count() }}</h3></div></div>
    <div class="col-md-3"><div class="card stat-card p-3"><small class="text-muted">تم التسليم</small><h3 class="text-success">{{ $shipments->where('shipment_status','delivered')->count() }}</h3></div></div>
    <div class="col-md-3"><div class="card stat-card p-3"><small class="text-muted">نقدية بعهدتي</small><h3>{{ number_format($cashInHand, 2) }}</h3></div></div>
    <div class="col-md-3"><div class="card stat-card p-3"><small class="text-muted">رصيد محفظتي</small><h3>{{ number_format($agent->wallet->current_balance, 2) }}</h3></div></div>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card p-3">
            <h6>الشحنات الموزعة</h6>
            <table class="table table-sm align-middle">
                <thead><tr><th>رقم التتبع</th><th>المندوب</th><th>الحالة</th><th></th></tr></thead>
                <tbody>
                @foreach ($shipments as $shipment)
                    <tr>
                        <td>{{ $shipment->tracking_number }}</td>
                        <td>{{ $shipment->assignedDriver?->name ?? '—' }}</td>
                        <td><x-status-badge :status="$shipment->shipment_status" /></td>
                        <td>
                            @if (!$shipment->assignedDriver)
                            <form method="POST" action="{{ route('agent.shipments.assign_driver', $shipment) }}" class="d-flex gap-1">
                                @csrf
                                <select name="driver_id" class="form-select form-select-sm">
                                    @foreach ($drivers as $driver)<option value="{{ $driver->id }}">{{ $driver->name }}</option>@endforeach
                                </select>
                                <button class="btn btn-sm btn-outline-primary">توزيع</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card p-3 mb-3">
            <h6>مندوبيّ</h6>
            <table class="table table-sm">
                <thead><tr><th>المندوب</th><th>مسلَّم</th><th>الإجمالي</th></tr></thead>
                <tbody>
                @foreach ($drivers as $driver)
                    <tr><td>{{ $driver->name }}</td><td>{{ $driver->delivered_count }}</td><td>{{ $driver->total_count }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card p-3">
            <h6>تسجيل توريد نقدية</h6>
            <form method="POST" action="{{ route('agent.handovers.store') }}" class="d-flex gap-2">
                @csrf
                <input type="number" step="0.01" name="amount" class="form-control form-control-sm" placeholder="المبلغ" required>
                <button class="btn btn-sm btn-primary">توريد</button>
            </form>
        </div>
    </div>
</div>
@endsection
