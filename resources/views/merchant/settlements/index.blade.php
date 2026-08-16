@extends('layouts.app')
@section('title', 'التسويات')
@section('sidebar')@include('partials.sidebar-merchant')@endsection

@section('content')
<div class="card p-3">
    <table class="table table-hover align-middle">
        <thead><tr><th>رقم التسوية</th><th>القيمة</th><th>الحالة</th><th>التاريخ</th><th>إثبات الدفع</th></tr></thead>
        <tbody>
        @foreach ($settlements as $settlement)
            <tr>
                <td>{{ $settlement->settlement_number }}</td>
                <td>{{ number_format($settlement->total_amount, 2) }}</td>
                <td><span class="badge bg-{{ $settlement->status === 'paid' ? 'success' : 'secondary' }}">{{ $settlement->status }}</span></td>
                <td class="small text-muted">{{ $settlement->paid_at?->format('Y-m-d') ?? $settlement->created_at->format('Y-m-d') }}</td>
                <td>
                    @foreach ($settlement->attachments as $att)
                        <a href="{{ asset('storage/'.$att->file_path) }}" target="_blank" class="badge bg-light text-dark">عرض الإثبات</a>
                    @endforeach
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $settlements->links() }}
</div>
@endsection
