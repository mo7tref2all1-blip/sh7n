@extends('layouts.app')
@section('title', 'لوحة الإدارة')
@section('sidebar')@include('partials.sidebar-admin')@endsection

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-2"><div class="card stat-card p-3"><small class="text-muted">شحنات اليوم</small><h3>{{ $kpis['today_count'] }}</h3></div></div>
    <div class="col-md-2"><div class="card stat-card p-3"><small class="text-muted">تم التسليم اليوم</small><h3 class="text-success">{{ $kpis['delivered_today'] }}</h3></div></div>
    <div class="col-md-2"><div class="card stat-card p-3"><small class="text-muted">مرتجعات اليوم</small><h3 class="text-danger">{{ $kpis['returns_today'] }}</h3></div></div>
    <div class="col-md-2"><div class="card stat-card p-3"><small class="text-muted">تحصيلات اليوم</small><h3>{{ number_format($kpis['collections_today'], 2) }}</h3></div></div>
    <div class="col-md-2"><div class="card stat-card p-3"><small class="text-muted">مستحقات التجار</small><h3>{{ number_format($kpis['merchant_dues'], 2) }}</h3></div></div>
    <div class="col-md-2"><div class="card stat-card p-3"><small class="text-muted">نقدية بعهدة المندوبين</small><h3>{{ number_format($kpis['driver_cash_in_hand'], 2) }}</h3></div></div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card p-3">
            <h6 class="mb-3">أفضل التجار (حسب الشحنات المسلَّمة)</h6>
            <table class="table table-sm">
                <thead><tr><th>التاجر</th><th>مسلَّم</th></tr></thead>
                <tbody>
                @foreach ($topMerchants as $merchant)
                    <tr><td><a href="{{ route('admin.merchants.show', $merchant) }}">{{ $merchant->business_name }}</a></td><td>{{ $merchant->delivered_count }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3">
            <h6 class="mb-3">آخر الأنشطة</h6>
            <ul class="list-group list-group-flush">
                @foreach ($recentActivity as $log)
                    <li class="list-group-item small">
                        <span class="text-muted">{{ $log->created_at->diffForHumans() }}</span> —
                        {{ $log->description }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
