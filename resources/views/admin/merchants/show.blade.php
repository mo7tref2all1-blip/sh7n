@extends('layouts.app')
@section('title', $merchant->business_name)
@section('sidebar')@include('partials.sidebar-admin')@endsection

@section('content')
<div class="row g-3">
    <div class="col-md-7">
        <div class="card p-3 mb-3">
            <h5>{{ $merchant->business_name }}</h5>
            <p class="text-muted mb-0">{{ $merchant->owner_name }} — {{ $merchant->phone }}</p>
            <p class="text-muted">رصيد المحفظة الحالي: <strong>{{ number_format($merchant->wallet->current_balance, 2) }} ج.م</strong></p>
        </div>
        <div class="card p-3">
            <h6>كشف حساب المحفظة</h6>
            <table class="table table-sm">
                <thead><tr><th>التاريخ</th><th>البيان</th><th>المبلغ</th><th>الرصيد بعدها</th></tr></thead>
                <tbody>
                @foreach ($merchant->wallet->transactions as $t)
                    <tr>
                        <td>{{ $t->created_at->format('Y-m-d') }}</td>
                        <td>{{ $t->description }}</td>
                        <td class="{{ $t->amount >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($t->amount, 2) }}</td>
                        <td>{{ number_format($t->balance_after, 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card p-3">
            <h6>إعدادات التاجر</h6>
            <form method="POST" action="{{ route('admin.merchants.update', $merchant) }}">
                @csrf @method('PUT')
                <label class="form-label small">الحالة</label>
                <select name="status" class="form-select form-select-sm mb-2">
                    @foreach (['pending','active','suspended'] as $s)<option value="{{ $s }}" @selected($merchant->status===$s)>{{ $s }}</option>@endforeach
                </select>
                <label class="form-label small">باقة تسعير الشحن</label>
                <select name="pricing_plan_id" class="form-select form-select-sm mb-2">
                    <option value="">افتراضي</option>
                    @foreach ($plans as $plan)<option value="{{ $plan->id }}" @selected($merchant->pricing_plan_id===$plan->id)>{{ $plan->name }}</option>@endforeach
                </select>
                <label class="form-label small">باقة تسعير المرتجعات</label>
                <select name="return_pricing_plan_id" class="form-select form-select-sm mb-2">
                    <option value="">افتراضي</option>
                    @foreach ($plans as $plan)<option value="{{ $plan->id }}" @selected($merchant->return_pricing_plan_id===$plan->id)>{{ $plan->name }}</option>@endforeach
                </select>
                <label class="form-label small">صورة إثبات التسليم</label>
                <select name="pod_photo_required" class="form-select form-select-sm mb-2">
                    @foreach (['optional'=>'اختياري','required'=>'إلزامي','disabled'=>'معطّل'] as $val=>$label)
                        <option value="{{ $val }}" @selected($merchant->pod_photo_required===$val)>{{ $label }}</option>
                    @endforeach
                </select>
                <label class="form-label small">توقيع العميل</label>
                <select name="pod_signature_required" class="form-select form-select-sm mb-2">
                    @foreach (['disabled'=>'معطّل','optional'=>'اختياري','required'=>'إلزامي'] as $val=>$label)
                        <option value="{{ $val }}" @selected($merchant->pod_signature_required===$val)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="form-check mb-2">
                    <input type="checkbox" name="free_returns" value="1" class="form-check-input" id="fr" @checked($merchant->free_returns)>
                    <label class="form-check-label small" for="fr">مرتجع مجاني بالكامل</label>
                </div>
                <label class="form-label small">نسبة تحمّل التاجر لرسوم المرتجع (%)</label>
                <input name="return_discount_percent" type="number" min="0" max="100" value="{{ $merchant->return_discount_percent }}" class="form-control form-control-sm mb-2">
                <label class="form-label small">الحد الأقصى لمحاولات التسليم</label>
                <input name="max_delivery_attempts" type="number" min="1" max="10" value="{{ $merchant->max_delivery_attempts }}" class="form-control form-control-sm mb-3">
                <button class="btn btn-primary btn-sm w-100">حفظ</button>
            </form>
        </div>
    </div>
</div>
@endsection
