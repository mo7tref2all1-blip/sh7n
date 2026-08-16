@extends('layouts.app')
@section('title', 'لوحة الفرع')
@section('sidebar')@include('partials.sidebar-branch')@endsection

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card stat-card p-3"><small class="text-muted">فرع</small><h5>{{ $branch->name }}</h5></div></div>
    <div class="col-md-4"><div class="card stat-card p-3"><small class="text-muted">عهدة نقدية الآن</small><h3>{{ number_format($cashInHand, 2) }}</h3></div></div>
    <div class="col-md-4"><div class="card stat-card p-3"><small class="text-muted">عدد الموظفين</small><h3>{{ $employees->count() }}</h3></div></div>
</div>

<div class="card p-3">
    <h6>توريدات نقدية بانتظار التأكيد</h6>
    <table class="table table-sm">
        <thead><tr><th>رقم التوريد</th><th>من</th><th>المبلغ</th><th></th></tr></thead>
        <tbody>
        @forelse ($pendingHandovers as $handover)
            <tr>
                <td>{{ $handover->handover_number }}</td>
                <td>{{ $handover->handedBy?->name }}</td>
                <td>{{ number_format($handover->amount, 2) }}</td>
                <td>
                    <form method="POST" action="{{ route('branch.handovers.confirm', $handover) }}">
                        @csrf
                        <button class="btn btn-sm btn-success">تأكيد الاستلام</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-muted text-center">لا يوجد توريدات معلّقة</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
