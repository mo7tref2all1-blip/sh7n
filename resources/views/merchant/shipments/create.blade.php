@extends('layouts.app')
@section('title', 'إنشاء شحنة')
@section('sidebar')@include('partials.sidebar-merchant')@endsection

@section('content')
<div class="card p-3" style="max-width:700px">
    <form method="POST" action="{{ route('merchant.shipments.store') }}">
        @csrf
        <h6>بيانات المستلم</h6>
        <div class="row g-2 mb-3">
            <div class="col-6"><label class="form-label small">الاسم</label><input name="consignee_name" class="form-control form-control-sm" required></div>
            <div class="col-6"><label class="form-label small">الهاتف</label><input name="consignee_phone" class="form-control form-control-sm" required></div>
            <div class="col-6"><label class="form-label small">هاتف بديل (اختياري)</label><input name="consignee_phone_alt" class="form-control form-control-sm"></div>
            <div class="col-6">
                <label class="form-label small">المحافظة</label>
                <select name="governorate_id" class="form-select form-select-sm" required>
                    @foreach ($governorates as $gov)<option value="{{ $gov->id }}">{{ $gov->name_ar }}</option>@endforeach
                </select>
            </div>
            <div class="col-12"><label class="form-label small">العنوان بالتفصيل</label><textarea name="address_text" class="form-control form-control-sm" required></textarea></div>
        </div>

        <h6>بيانات الطرد</h6>
        <div class="row g-2 mb-3">
            <div class="col-6"><label class="form-label small">الوصف</label><input name="package_description" class="form-control form-control-sm"></div>
            <div class="col-3"><label class="form-label small">الوزن (كجم)</label><input name="weight_kg" type="number" step="0.1" value="1" class="form-control form-control-sm"></div>
            <div class="col-3">
                <label class="form-label small">نوع الخدمة</label>
                <select name="service_type" class="form-select form-select-sm">
                    <option value="normal">عادي</option>
                    <option value="express">إكسبريس</option>
                    <option value="same_day">نفس اليوم</option>
                </select>
            </div>
        </div>

        <h6>الدفع</h6>
        <div class="row g-2 mb-3">
            <div class="col-6">
                <label class="form-label small">طريقة الدفع</label>
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="cod">الدفع عند الاستلام (COD)</option>
                    <option value="prepaid">مدفوع مسبقًا</option>
                    <option value="visa_on_delivery">فيزا عند الاستلام</option>
                    <option value="bank_transfer">تحويل بنكي</option>
                    <option value="wallet_payment">محفظة إلكترونية</option>
                </select>
            </div>
            <div class="col-6"><label class="form-label small">المبلغ المطلوب تحصيله</label><input name="amount_to_collect" type="number" step="0.01" value="0" class="form-control form-control-sm"></div>
            <div class="col-6"><label class="form-label small">رقم الأوردر الخاص بك (اختياري)</label><input name="reference_number" class="form-control form-control-sm"></div>
        </div>

        <button class="btn btn-primary">حفظ وطباعة البوليصة</button>
    </form>
</div>
@endsection
