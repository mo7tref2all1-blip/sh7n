@extends('layouts.app')
@section('title', 'الشحنات')
@section('sidebar')@include('partials.sidebar-admin')@endsection

@section('content')
<div class="card p-3">
    <form class="row g-2 mb-3">
        <div class="col-auto">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">كل الحالات</option>
                @foreach ([\App\Models\Shipment::STATUS_CREATED, \App\Models\Shipment::STATUS_OUT_FOR_DELIVERY, \App\Models\Shipment::STATUS_DELIVERED, \App\Models\Shipment::STATUS_RETURNED, \App\Models\Shipment::STATUS_NO_ANSWER] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ \App\Support\StatusLabels::shipment($status)[0] }}</option>
                @endforeach
            </select>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>رقم التتبع</th><th>التاجر</th><th>المستلم</th><th>المحافظة</th><th>الحالة</th><th>الحالة المالية</th><th>المبلغ</th><th></th></tr></thead>
            <tbody>
            @foreach ($shipments as $shipment)
                <tr>
                    <td><a href="{{ route('admin.shipments.show', $shipment) }}">{{ $shipment->tracking_number }}</a></td>
                    <td>{{ $shipment->merchant->business_name }}</td>
                    <td>{{ $shipment->consignee_name }}</td>
                    <td>{{ $shipment->governorate->name_ar }}</td>
                    <td><x-status-badge :status="$shipment->shipment_status" /></td>
                    <td><x-financial-badge :status="$shipment->financial_status" /></td>
                    <td>{{ number_format($shipment->amount_to_collect, 2) }}</td>
                    <td><a href="{{ route('admin.shipments.show', $shipment) }}" class="btn btn-sm btn-outline-primary">تفاصيل</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $shipments->links() }}
</div>
@endsection
