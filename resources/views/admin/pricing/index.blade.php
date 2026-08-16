@extends('layouts.app')
@section('title', 'التسعير')
@section('sidebar')@include('partials.sidebar-admin')@endsection

@section('content')
<div class="row g-3">
    <div class="col-md-7">
        <div class="card p-3 mb-3">
            <h6>الباقات وقواعد التسعير العامة</h6>
            @foreach ($plans as $plan)
                <h6 class="mt-3 text-primary">{{ $plan->name }}</h6>
                <table class="table table-sm">
                    <thead><tr><th>المحافظة</th><th>الوزن</th><th>سعر الشحن</th><th>سعر المرتجع</th></tr></thead>
                    <tbody>
                    @foreach ($plan->rules as $rule)
                        <tr>
                            <td>{{ $rule->governorate?->name_ar ?? 'الكل' }}</td>
                            <td>{{ $rule->weight_from }} - {{ $rule->weight_to }} كجم</td>
                            <td>{{ number_format($rule->price, 2) }}</td>
                            <td>{{ number_format($rule->return_price, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endforeach
        </div>
    </div>
    <div class="col-md-5">
        <div class="card p-3">
            <h6>تسعير مخصص لتاجر (Override)</h6>
            <p class="small text-muted">يتفوق على تسعير الباقة تلقائيًا عند الحساب.</p>
            <form method="POST" action="{{ route('admin.pricing.merchant.store', ['merchant' => 0]) }}" id="merchant-pricing-form">
                @csrf
                <label class="form-label small">التاجر</label>
                <select class="form-select form-select-sm mb-2" onchange="document.getElementById('merchant-pricing-form').action = document.getElementById('merchant-pricing-form').action.replace(/\/0$|\/\d+$/, '/'+this.value)">
                    @foreach ($merchants as $merchant)<option value="{{ $merchant->id }}">{{ $merchant->business_name }}</option>@endforeach
                </select>
                <label class="form-label small">المحافظة</label>
                <select name="governorate_id" class="form-select form-select-sm mb-2" required>
                    @foreach ($governorates as $gov)<option value="{{ $gov->id }}">{{ $gov->name_ar }}</option>@endforeach
                </select>
                <label class="form-label small">نوع الخدمة</label>
                <select name="service_type" class="form-select form-select-sm mb-2">
                    <option value="normal">عادي</option>
                    <option value="express">إكسبريس</option>
                    <option value="same_day">نفس اليوم</option>
                </select>
                <label class="form-label small">سعر الشحن</label>
                <input name="price" type="number" step="0.01" class="form-control form-control-sm mb-2" required>
                <label class="form-label small">سعر المرتجع</label>
                <input name="return_price" type="number" step="0.01" class="form-control form-control-sm mb-2" required>
                <button class="btn btn-primary btn-sm w-100">حفظ</button>
            </form>
        </div>
    </div>
</div>
@endsection
