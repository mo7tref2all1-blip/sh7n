@extends('layouts.app')
@section('title', 'وكلاء الشحن')
@section('sidebar')@include('partials.sidebar-admin')@endsection

@section('content')
<div class="row g-3">
    <div class="col-md-8">
        <div class="card p-3">
            <table class="table table-hover align-middle">
                <thead><tr><th>الوكيل</th><th>المحافظة الحصرية</th><th>مندوبين</th><th>شحنات</th><th>رصيد المحفظة</th></tr></thead>
                <tbody>
                @foreach ($agents as $agent)
                    <tr>
                        <td>{{ $agent->name }}</td>
                        <td>{{ $agent->governorate->name_ar }}</td>
                        <td>{{ $agent->drivers_count }}</td>
                        <td>{{ $agent->shipments_count }}</td>
                        <td>{{ number_format($agent->wallet->current_balance ?? 0, 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3">
            <h6>وكيل شحن جديد</h6>
            <form method="POST" action="{{ route('admin.agents.store') }}">
                @csrf
                <div class="mb-2"><label class="form-label small">الاسم</label><input name="name" class="form-control form-control-sm" required></div>
                <div class="mb-2"><label class="form-label small">الهاتف</label><input name="phone" class="form-control form-control-sm" required></div>
                <div class="mb-2">
                    <label class="form-label small">المحافظة الحصرية</label>
                    <select name="governorate_id" class="form-select form-select-sm" required>
                        @foreach ($governorates as $gov)<option value="{{ $gov->id }}">{{ $gov->name_ar }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small">نوع العمولة</label>
                    <select name="commission_type" class="form-select form-select-sm">
                        <option value="fixed">مبلغ ثابت لكل شحنة</option>
                        <option value="percentage">نسبة من رسوم الشحن</option>
                    </select>
                </div>
                <div class="mb-2"><label class="form-label small">قيمة العمولة</label><input name="commission_value" type="number" step="0.01" class="form-control form-control-sm" required></div>
                <div class="mb-2"><label class="form-label small">كلمة مرور الدخول</label><input name="login_password" type="password" class="form-control form-control-sm" required></div>
                <button class="btn btn-primary btn-sm w-100">إنشاء</button>
            </form>
        </div>
    </div>
</div>
@endsection
