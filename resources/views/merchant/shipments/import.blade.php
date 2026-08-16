@extends('layouts.app')
@section('title', 'رفع شحنات Excel')
@section('sidebar')@include('partials.sidebar-merchant')@endsection

@section('content')
<div class="row g-3">
    <div class="col-md-6">
        <div class="card p-3">
            <h6>رفع ملف شحنات جماعي</h6>
            <p class="small text-muted">الأعمدة المطلوبة: consignee_name, consignee_phone, governorate, address, description, weight_kg, amount_to_collect, reference_number</p>
            <form method="POST" action="{{ route('merchant.shipments.import') }}" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file" class="form-control mb-2" required accept=".xlsx,.xls,.csv">
                <button class="btn btn-primary btn-sm">رفع ومعالجة</button>
            </form>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3">
            <h6>آخر عمليات الرفع</h6>
            <table class="table table-sm">
                <thead><tr><th>الملف</th><th>الحالة</th><th>ناجح</th><th>أخطاء</th></tr></thead>
                <tbody>
                @foreach ($imports as $import)
                    <tr>
                        <td class="small">{{ $import->original_filename }}</td>
                        <td><span class="badge bg-{{ $import->status === 'completed' ? 'success' : 'warning' }}">{{ $import->status }}</span></td>
                        <td>{{ $import->success_count }}</td>
                        <td>{{ $import->error_count }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
