@extends('layouts.app')
@section('title', $shipment->tracking_number)

@section('content')
<div class="card p-3 mb-3">
    <h5>{{ $shipment->tracking_number }}</h5>
    <p class="mb-1">{{ $shipment->consignee_name }} — <a href="tel:{{ $shipment->consignee_phone }}">{{ $shipment->consignee_phone }}</a></p>
    <p class="text-muted mb-1">{{ $shipment->governorate->name_ar }} — {{ $shipment->address_text }}</p>
    <p class="mb-0">المبلغ المطلوب تحصيله: <strong>{{ number_format($shipment->amount_to_collect, 2) }} ج.م</strong></p>
    <x-status-badge :status="$shipment->shipment_status" />
</div>

<div id="gps-status" class="alert alert-secondary small">📍 جاري تحديد الموقع...</div>

@if ($shipment->shipment_status === \App\Models\Shipment::STATUS_CREATED || $shipment->shipment_status === \App\Models\Shipment::STATUS_READY_FOR_PICKUP)
    <form method="POST" action="{{ route('driver.shipments.pickup', $shipment) }}">
        @csrf
        <button class="btn btn-primary w-100 py-3 mb-2">📷 استلام الشحنة (Scan)</button>
    </form>
@else
    <div class="d-grid gap-2 mb-3">
        <button class="btn btn-success py-3" data-bs-toggle="modal" data-bs-target="#deliverModal">✅ تم التسليم</button>
        <button class="btn btn-outline-danger py-2" data-bs-toggle="modal" data-bs-target="#failModal">⚠️ لم يتم التسليم</button>
    </div>
@endif

<!-- Deliver modal -->
<div class="modal fade" id="deliverModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('driver.shipments.deliver', $shipment) }}" enctype="multipart/form-data" id="deliver-form">
      @csrf
      <div class="modal-header"><h6 class="modal-title">تأكيد التسليم</h6></div>
      <div class="modal-body">
        <label class="form-label small">المبلغ المحصَّل</label>
        <input type="number" step="0.01" name="amount_collected" class="form-control mb-2" value="{{ $shipment->amount_to_collect }}">

        <label class="form-label small">صورة إثبات التسليم @if($shipment->merchant->pod_photo_required==='required')<span class="text-danger">(إلزامي)</span>@endif</label>
        <input type="file" name="photo" accept="image/*" capture="environment" class="form-control mb-2" @required($shipment->merchant->pod_photo_required==='required')>

        @if ($shipment->merchant->pod_signature_required !== 'disabled')
        <label class="form-label small">توقيع العميل @if($shipment->merchant->pod_signature_required==='required')<span class="text-danger">(إلزامي)</span>@endif</label>
        <input type="file" name="signature" accept="image/*" class="form-control mb-2" @required($shipment->merchant->pod_signature_required==='required')>
        @endif

        <label class="form-label small">ملاحظات (اختياري)</label>
        <textarea name="notes" class="form-control mb-2"></textarea>

        <input type="hidden" name="gps_lat" class="gps-lat" required>
        <input type="hidden" name="gps_lng" class="gps-lng" required>
      </div>
      <div class="modal-footer"><button class="btn btn-success w-100">✅ تأكيد التسليم</button></div>
    </form>
  </div></div>
</div>

<!-- Fail modal -->
<div class="modal fade" id="failModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('driver.shipments.fail', $shipment) }}" enctype="multipart/form-data" id="fail-form">
      @csrf
      <div class="modal-header"><h6 class="modal-title">لماذا لم يتم التسليم؟</h6></div>
      <div class="modal-body">
        <div class="form-check"><input class="form-check-input" type="radio" name="result" value="no_answer" id="r1" required><label class="form-check-label" for="r1">لا يرد</label></div>
        <div class="form-check"><input class="form-check-input" type="radio" name="result" value="postponed" id="r2"><label class="form-check-label" for="r2">مؤجل</label></div>
        <div class="form-check"><input class="form-check-input" type="radio" name="result" value="refused_receipt" id="r3"><label class="form-check-label" for="r3">رفض الاستلام</label></div>
        <div class="form-check"><input class="form-check-input" type="radio" name="result" value="refused_receipt_fled" id="r4"><label class="form-check-label text-danger" for="r4">رفض الاستلام وهروب</label></div>
        <div class="form-check"><input class="form-check-input" type="radio" name="result" value="refused_price" id="r5"><label class="form-check-label" for="r5">رفض السعر</label></div>
        <div class="form-check"><input class="form-check-input" type="radio" name="result" value="wrong_address" id="r6"><label class="form-check-label" for="r6">عنوان خاطئ</label></div>

        <div id="postpone-date-wrap" class="d-none mt-2">
            <label class="form-label small">تأجيل حتى تاريخ</label>
            <input type="date" name="postponed_to_date" class="form-control">
        </div>
        <div id="screenshot-wrap" class="d-none mt-2">
            <label class="form-label small">سكرين شوت محاولة الاتصال (إلزامي)</label>
            <input type="file" name="call_screenshot" accept="image/*" class="form-control">
        </div>

        <label class="form-label small mt-2">ملاحظة إضافية</label>
        <textarea name="notes" class="form-control"></textarea>

        <input type="hidden" name="gps_lat" class="gps-lat">
        <input type="hidden" name="gps_lng" class="gps-lng">
      </div>
      <div class="modal-footer"><button class="btn btn-danger w-100">تأكيد</button></div>
    </form>
  </div></div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Free browser Geolocation API — no paid API needed (docs/02-SRS.md § 2.2.8.1).
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (pos) {
            document.querySelectorAll('.gps-lat').forEach(el => el.value = pos.coords.latitude);
            document.querySelectorAll('.gps-lng').forEach(el => el.value = pos.coords.longitude);
            document.getElementById('gps-status').textContent = '📍 تم تحديد الموقع بنجاح';
            document.getElementById('gps-status').className = 'alert alert-success small';
        }, function () {
            document.getElementById('gps-status').textContent = '⚠️ تعذر تحديد الموقع — فعّل خدمة الموقع';
            document.getElementById('gps-status').className = 'alert alert-warning small';
        });
    }

    document.querySelectorAll('input[name=result]').forEach(el => el.addEventListener('change', function () {
        document.getElementById('postpone-date-wrap').classList.toggle('d-none', this.value !== 'postponed');
        document.getElementById('screenshot-wrap').classList.toggle('d-none', this.value !== 'no_answer');
        document.querySelector('#screenshot-wrap input').required = (this.value === 'no_answer');
    }));
});
</script>
@endpush
