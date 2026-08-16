@extends('layouts.app')
@section('title', 'التجار')
@section('sidebar')@include('partials.sidebar-admin')@endsection

@section('content')
<div class="card p-3">
    <div class="d-flex justify-content-between mb-3">
        <h6 class="mb-0">قائمة التجار</h6>
        <a href="{{ route('admin.merchants.create') }}" class="btn btn-primary btn-sm">+ تاجر جديد</a>
    </div>
    <table class="table table-hover align-middle">
        <thead><tr><th>التاجر</th><th>الهاتف</th><th>الحالة</th><th>عدد الشحنات</th><th>رصيد المحفظة</th></tr></thead>
        <tbody>
        @foreach ($merchants as $merchant)
            <tr>
                <td><a href="{{ route('admin.merchants.show', $merchant) }}">{{ $merchant->business_name }}</a></td>
                <td>{{ $merchant->phone }}</td>
                <td><span class="badge bg-{{ $merchant->status === 'active' ? 'success' : 'secondary' }}">{{ $merchant->status }}</span></td>
                <td>{{ $merchant->shipments_count }}</td>
                <td>{{ number_format($merchant->wallet->current_balance ?? 0, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $merchants->links() }}
</div>
@endsection
