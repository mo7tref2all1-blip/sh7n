@extends('layouts.app')
@section('title', 'التسويات')
@section('sidebar')@include('partials.sidebar-accountant')@endsection

@section('content')
<div class="card p-3">
    <table class="table table-hover align-middle">
        <thead><tr><th>رقم التسوية</th><th>التاجر</th><th>القيمة</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
        @foreach ($settlements as $settlement)
            <tr>
                <td>{{ $settlement->settlement_number }}</td>
                <td>{{ $settlement->merchant->business_name }}</td>
                <td>{{ number_format($settlement->total_amount, 2) }}</td>
                <td><span class="badge bg-secondary">{{ $settlement->status }}</span></td>
                <td>
                    @if ($settlement->status === 'draft')
                        <form method="POST" action="{{ route('accountant.settlements.approve', $settlement) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-primary">اعتماد</button>
                        </form>
                    @elseif ($settlement->status === 'approved')
                        <form method="POST" action="{{ route('accountant.settlements.pay', $settlement) }}" enctype="multipart/form-data" class="d-flex gap-1">
                            @csrf
                            <input type="file" name="proof" class="form-control form-control-sm" required accept="image/*,.pdf">
                            <button class="btn btn-sm btn-success">دفع + إثبات</button>
                        </form>
                    @else
                        <span class="text-success small">✔ تم الدفع</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $settlements->links() }}
</div>
@endsection
