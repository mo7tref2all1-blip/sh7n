@extends('layouts.app')
@section('title', 'شحناتي')
@section('sidebar')@include('partials.sidebar-merchant')@endsection

@section('content')
<div class="card p-3">
    <table class="table table-hover align-middle">
        <thead><tr><th>رقم التتبع</th><th>المستلم</th><th>الحالة</th><th>الحالة المالية</th><th>المبلغ</th><th>تاريخ الإنشاء</th></tr></thead>
        <tbody>
        @foreach ($shipments as $shipment)
            <tr>
                <td><a href="{{ route('merchant.shipments.show', $shipment) }}">{{ $shipment->tracking_number }}</a></td>
                <td>{{ $shipment->consignee_name }}</td>
                <td><x-status-badge :status="$shipment->shipment_status" /></td>
                <td><x-financial-badge :status="$shipment->financial_status" /></td>
                <td>{{ number_format($shipment->amount_to_collect, 2) }}</td>
                <td class="small text-muted">{{ $shipment->created_at->format('Y-m-d') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $shipments->links() }}
</div>
@endsection
