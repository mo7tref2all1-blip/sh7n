@extends('layouts.app')
@section('title', 'لوحة المحاسب')
@section('sidebar')@include('partials.sidebar-accountant')@endsection

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card stat-card p-3"><small class="text-muted">توريدات بانتظار التأكيد</small><h3>{{ $pendingHandovers }}</h3></div></div>
    <div class="col-md-4"><div class="card stat-card p-3"><small class="text-muted">إجمالي مستحقات التجار</small><h3>{{ number_format($merchants->sum(fn($m)=>$m->wallet->current_balance ?? 0), 2) }}</h3></div></div>
</div>

<div class="card p-3">
    <h6>مستحقات التجار (رصيد المحفظة القابل للتسوية)</h6>
    <table class="table table-hover align-middle">
        <thead><tr><th>التاجر</th><th>الرصيد المستحق</th><th></th></tr></thead>
        <tbody>
        @foreach ($merchants as $merchant)
            <tr>
                <td>{{ $merchant->business_name }}</td>
                <td class="{{ ($merchant->wallet->current_balance ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($merchant->wallet->current_balance ?? 0, 2) }}</td>
                <td>
                    @if (($merchant->wallet->current_balance ?? 0) > 0)
                    <form method="POST" action="{{ route('accountant.settlements.store', $merchant) }}">
                        @csrf
                        <button class="btn btn-sm btn-primary">إنشاء تسوية</button>
                    </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
