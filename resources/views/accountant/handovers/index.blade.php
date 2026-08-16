@extends('layouts.app')
@section('title', 'توريدات نقدية')
@section('sidebar')@include('partials.sidebar-accountant')@endsection

@section('content')
<div class="card p-3">
    <table class="table table-hover align-middle">
        <thead><tr><th>رقم التوريد</th><th>من</th><th>المبلغ</th><th></th></tr></thead>
        <tbody>
        @forelse ($handovers as $handover)
            <tr>
                <td>{{ $handover->handover_number }}</td>
                <td>{{ ucfirst($handover->from_type) }} — {{ $handover->handedBy?->name }}</td>
                <td>{{ number_format($handover->amount, 2) }}</td>
                <td>
                    <form method="POST" action="{{ route('accountant.handovers.confirm', $handover) }}">
                        @csrf
                        <button class="btn btn-sm btn-success">تأكيد الاستلام</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-muted text-center">لا يوجد توريدات معلّقة</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
