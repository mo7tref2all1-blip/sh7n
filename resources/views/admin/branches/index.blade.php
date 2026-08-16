@extends('layouts.app')
@section('title', 'الفروع')
@section('sidebar')@include('partials.sidebar-admin')@endsection

@section('content')
<div class="row g-3">
    <div class="col-md-8">
        <div class="card p-3">
            <table class="table table-hover align-middle">
                <thead><tr><th>الكود</th><th>الاسم</th><th>المحافظة</th><th>المدير</th><th>مخزون حالي</th></tr></thead>
                <tbody>
                @foreach ($branches as $branch)
                    <tr>
                        <td>{{ $branch->code }}</td>
                        <td>{{ $branch->name }}</td>
                        <td>{{ $branch->governorate->name_ar }}</td>
                        <td>{{ $branch->manager?->name ?? '—' }}</td>
                        <td><span class="badge bg-secondary">{{ $branch->custody_shipments_count }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3">
            <h6>إنشاء فرع جديد</h6>
            <form method="POST" action="{{ route('admin.branches.store') }}">
                @csrf
                <div class="mb-2"><label class="form-label small">الاسم</label><input name="name" class="form-control form-control-sm" required></div>
                <div class="mb-2"><label class="form-label small">الكود</label><input name="code" class="form-control form-control-sm" required></div>
                <div class="mb-2">
                    <label class="form-label small">المحافظة</label>
                    <select name="governorate_id" class="form-select form-select-sm" required>
                        @foreach ($governorates as $gov)<option value="{{ $gov->id }}">{{ $gov->name_ar }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-2"><label class="form-label small">العنوان</label><input name="address" class="form-control form-control-sm"></div>
                <div class="mb-2"><label class="form-label small">الهاتف</label><input name="phone" class="form-control form-control-sm"></div>
                <button class="btn btn-primary btn-sm w-100">إنشاء</button>
            </form>
        </div>
    </div>
</div>
@endsection
