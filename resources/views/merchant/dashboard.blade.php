@extends('layouts.app')
@section('title', 'لوحة التاجر')
@section('sidebar')@include('partials.sidebar-merchant')@endsection

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-2"><div class="card stat-card p-3"><small class="text-muted">إجمالي الشحنات</small><h3>{{ $kpis['total'] }}</h3></div></div>
    <div class="col-md-2"><div class="card stat-card p-3"><small class="text-muted">تم التسليم</small><h3 class="text-success">{{ $kpis['delivered'] }}</h3></div></div>
    <div class="col-md-2"><div class="card stat-card p-3"><small class="text-muted">مرتجعات</small><h3 class="text-danger">{{ $kpis['returned'] }}</h3></div></div>
    <div class="col-md-2"><div class="card stat-card p-3"><small class="text-muted">نسبة النجاح</small><h3>{{ $kpis['success_rate'] }}%</h3></div></div>
    <div class="col-md-2"><div class="card stat-card p-3"><small class="text-muted">إجمالي التحصيلات</small><h3>{{ number_format($kpis['total_collected'], 0) }}</h3></div></div>
    <div class="col-md-2"><div class="card stat-card p-3"><small class="text-muted">رصيدي المستحق</small><h3 class="{{ $kpis['wallet_balance'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($kpis['wallet_balance'], 0) }}</h3></div></div>
</div>

<div class="card p-3">
    <h6>آخر الشحنات</h6>
    <table class="table table-hover align-middle">
        <thead><tr><th>رقم التتبع</th><th>المستلم</th><th>الحالة</th><th>الحالة المالية</th><th>المبلغ</th></tr></thead>
        <tbody>
        @foreach ($recentShipments as $shipment)
            <tr>
                <td><a href="{{ route('merchant.shipments.show', $shipment) }}">{{ $shipment->tracking_number }}</a></td>
                <td>{{ $shipment->consignee_name }}</td>
                <td><x-status-badge :status="$shipment->shipment_status" /></td>
                <td><x-financial-badge :status="$shipment->financial_status" /></td>
                <td>{{ number_format($shipment->amount_to_collect, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
