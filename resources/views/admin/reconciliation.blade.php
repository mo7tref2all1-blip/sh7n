@extends('layouts.app')
@section('title', 'لوحة المطابقة المالية')
@section('sidebar')@include('partials.sidebar-admin')@endsection

@section('content')
<div class="card p-3">
    <p class="text-muted small">الشحنات المُسلَّمة التي لم تُسوَّى ماليًا بعد للتاجر — يجب أن تكون كل شحنة "تم التسليم" إما بعهدة مندوب أو وصلت للفرع/الشركة، وليست "غير محصَّل" (تنبيه محتمل).</p>
    <table class="table table-hover align-middle">
        <thead><tr><th>رقم التتبع</th><th>التاجر</th><th>الحالة التشغيلية</th><th>الحالة المالية</th><th>حامل العهدة</th><th>المبلغ</th></tr></thead>
        <tbody>
        @foreach ($shipments as $shipment)
            <tr class="{{ $shipment->financial_status === \App\Models\Shipment::FIN_UNCOLLECTED ? 'table-danger' : '' }}">
                <td><a href="{{ route('admin.shipments.show', $shipment) }}">{{ $shipment->tracking_number }}</a></td>
                <td>{{ $shipment->merchant->business_name }}</td>
                <td><x-status-badge :status="$shipment->shipment_status" /></td>
                <td><x-financial-badge :status="$shipment->financial_status" /></td>
                <td>{{ $shipment->assignedDriver?->name ?? '—' }}</td>
                <td>{{ number_format($shipment->amount_collected, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $shipments->links() }}
</div>
@endsection
