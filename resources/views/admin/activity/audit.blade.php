@extends('layouts.app')
@section('title', 'سجل التدقيق')
@section('sidebar')@include('partials.sidebar-admin')@endsection

@section('content')
<div class="card p-3">
    <table class="table table-sm">
        <thead><tr><th>الوقت</th><th>المستخدم</th><th>الإجراء</th><th>النموذج</th><th>IP</th></tr></thead>
        <tbody>
        @foreach ($logs as $log)
            <tr>
                <td class="small text-muted">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                <td>{{ $log->user?->name ?? '—' }}</td>
                <td>{{ $log->action }}</td>
                <td>{{ class_basename($log->model_type) }} #{{ $log->model_id }}</td>
                <td class="small text-muted">{{ $log->ip_address }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $logs->links() }}
</div>
@endsection
