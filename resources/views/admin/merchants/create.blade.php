@extends('layouts.app')
@section('title', 'تاجر جديد')
@section('sidebar')@include('partials.sidebar-admin')@endsection

@section('content')
<div class="card p-3" style="max-width:600px">
    <form method="POST" action="{{ route('admin.merchants.store') }}">
        @csrf
        <div class="row g-2">
            <div class="col-6"><label class="form-label small">اسم النشاط التجاري</label><input name="business_name" class="form-control form-control-sm" required></div>
            <div class="col-6"><label class="form-label small">اسم صاحب النشاط</label><input name="owner_name" class="form-control form-control-sm" required></div>
            <div class="col-6"><label class="form-label small">الهاتف (سيُستخدم للدخول)</label><input name="phone" class="form-control form-control-sm" required></div>
            <div class="col-6"><label class="form-label small">البريد الإلكتروني</label><input name="email" type="email" class="form-control form-control-sm"></div>
            <div class="col-6">
                <label class="form-label small">المحافظة</label>
                <select name="governorate_id" class="form-select form-select-sm" required>
                    @foreach ($governorates as $gov)<option value="{{ $gov->id }}">{{ $gov->name_ar }}</option>@endforeach
                </select>
            </div>
            <div class="col-6"><label class="form-label small">كلمة مرور الدخول</label><input name="owner_login_password" type="password" class="form-control form-control-sm" required></div>

            <div class="col-6">
                <label class="form-label small">باقة تسعير الشحن</label>
                <select name="pricing_plan_id" class="form-select form-select-sm">
                    <option value="">افتراضي</option>
                    @foreach ($plans as $plan)<option value="{{ $plan->id }}">{{ $plan->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-6">
                <label class="form-label small">باقة تسعير المرتجعات</label>
                <select name="return_pricing_plan_id" class="form-select form-select-sm">
                    <option value="">افتراضي</option>
                    @foreach ($plans as $plan)<option value="{{ $plan->id }}">{{ $plan->name }}</option>@endforeach
                </select>
            </div>

            <div class="col-6">
                <label class="form-label small">صورة إثبات التسليم</label>
                <select name="pod_photo_required" class="form-select form-select-sm">
                    <option value="optional">اختياري</option>
                    <option value="required">إلزامي</option>
                    <option value="disabled">معطّل</option>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label small">توقيع العميل</label>
                <select name="pod_signature_required" class="form-select form-select-sm">
                    <option value="disabled">معطّل</option>
                    <option value="optional">اختياري</option>
                    <option value="required">إلزامي</option>
                </select>
            </div>

            <div class="col-6">
                <div class="form-check mt-4">
                    <input type="checkbox" name="free_returns" value="1" class="form-check-input" id="free_returns">
                    <label class="form-check-label small" for="free_returns">مرتجع مجاني بالكامل لهذا التاجر</label>
                </div>
            </div>
            <div class="col-6">
                <label class="form-label small">نسبة تحمّل التاجر لرسوم المرتجع (%)</label>
                <input name="return_discount_percent" type="number" min="0" max="100" value="100" class="form-control form-control-sm">
            </div>
        </div>
        <button class="btn btn-primary btn-sm mt-3">إنشاء التاجر</button>
    </form>
</div>
@endsection
