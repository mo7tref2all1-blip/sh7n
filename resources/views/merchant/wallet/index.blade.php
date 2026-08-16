@extends('layouts.app')
@section('title', 'المحفظة')
@section('sidebar')@include('partials.sidebar-merchant')@endsection

@section('content')
<div class="card p-3 mb-3">
    <h4>الرصيد الحالي: <span class="{{ $wallet->current_balance >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($wallet->current_balance, 2) }} ج.م</span></h4>
</div>
<div class="card p-3">
    <table class="table table-hover">
        <thead><tr><th>التاريخ</th><th>البيان</th><th>المبلغ</th><th>الرصيد بعدها</th></tr></thead>
        <tbody>
        @foreach ($wallet->transactions as $t)
            <tr>
                <td class="small text-muted">{{ $t->created_at->format('Y-m-d H:i') }}</td>
                <td>{{ $t->description }}</td>
                <td class="{{ $t->amount >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($t->amount, 2) }}</td>
                <td>{{ number_format($t->balance_after, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
