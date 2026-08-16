@extends('layouts.app')
@section('title', 'شحنة '.$shipment->tracking_number)
@section('sidebar')@include('partials.sidebar-admin')@endsection

@section('content')
<div class="row g-3">
    <div class="col-md-8">
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5>{{ $shipment->tracking_number }}</h5>
                    <p class="mb-1">المستلم: {{ $shipment->consignee_name }} — {{ $shipment->consignee_phone }}</p>
                    <p class="mb-1 text-muted">{{ $shipment->governorate->name_ar }} — {{ $shipment->address_text }}</p>
                </div>
                <div class="text-end">
                    <x-status-badge :status="$shipment->shipment_status" /><br>
                    <span class="mt-1 d-inline-block"><x-financial-badge :status="$shipment->financial_status" /></span>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-4"><small class="text-muted">التاجر</small><div>{{ $shipment->merchant->business_name }}</div></div>
                <div class="col-4"><small class="text-muted">المبلغ المطلوب</small><div>{{ number_format($shipment->amount_to_collect, 2) }} ج.م</div></div>
                <div class="col-4"><small class="text-muted">المحصَّل</small><div>{{ number_format($shipment->amount_collected, 2) }} ج.م</div></div>
            </div>
        </div>

        <div class="card p-3 mb-3">
            <h6>سجل الحالات (Timeline)</h6>
            <ul class="list-group list-group-flush">
                @foreach ($shipment->statusHistory as $h)
                    <li class="list-group-item d-flex justify-content-between">
                        <div>
                            <x-status-badge :status="$h->status" />
                            <span class="ms-2 text-muted small">{{ $h->changedBy?->name }}</span>
                            @if ($h->reason) <div class="small text-muted">{{ $h->reason }}</div> @endif
                        </div>
                        <div class="text-end small text-muted">
                            {{ $h->created_at->format('Y-m-d H:i') }}
                            @if ($h->googleMapsUrl())
                                <br><a href="{{ $h->googleMapsUrl() }}" target="_blank">📍 عرض على الخريطة</a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        @if ($shipment->pod)
        <div class="card p-3 mb-3">
            <h6>إثبات التسليم (POD)</h6>
            <p class="small">تم التسجيل: {{ $shipment->pod->recorded_at->format('Y-m-d H:i') }} — <a href="{{ $shipment->pod->googleMapsUrl() }}" target="_blank">📍 الموقع</a></p>
            @if ($shipment->pod->photo_path)<img src="{{ asset('storage/'.$shipment->pod->photo_path) }}" class="img-thumbnail" style="max-width:200px">@endif
            @if ($shipment->pod->notes)<p class="mt-2">{{ $shipment->pod->notes }}</p>@endif
        </div>
        @endif
    </div>

    <div class="col-md-4">
        <div class="card p-3">
            <h6>تعيين الشحنة</h6>
            <form method="POST" action="{{ route('admin.shipments.assign', $shipment) }}">
                @csrf
                <label class="form-label small">مندوب</label>
                <select name="driver_id" class="form-select form-select-sm mb-2">
                    <option value="">— بدون —</option>
                    @foreach ($drivers as $driver)
                        <option value="{{ $driver->id }}" @selected($shipment->assigned_driver_id === $driver->id)>{{ $driver->name }}</option>
                    @endforeach
                </select>
                <label class="form-label small">أو وكيل</label>
                <select name="agent_id" class="form-select form-select-sm mb-2">
                    <option value="">— بدون —</option>
                    @foreach ($agents as $agent)
                        <option value="{{ $agent->id }}" @selected($shipment->assigned_agent_id === $agent->id)>{{ $agent->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary btn-sm w-100">حفظ التعيين</button>
            </form>
        </div>

        <div class="card p-3 mt-3">
            <h6>محاولات التسليم</h6>
            @forelse ($shipment->deliveryAttempts as $attempt)
                <div class="small border-bottom py-1">
                    #{{ $attempt->attempt_number }} — {{ \App\Support\StatusLabels::deliveryResult($attempt->result) }}
                    <div class="text-muted">{{ $attempt->attempted_at->format('Y-m-d H:i') }}</div>
                    @if ($attempt->call_screenshot_path)
                        <a href="{{ asset('storage/'.$attempt->call_screenshot_path) }}" target="_blank">سكرين شوت المكالمة</a>
                    @endif
                </div>
            @empty
                <p class="text-muted small mb-0">لا توجد محاولات فاشلة.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
