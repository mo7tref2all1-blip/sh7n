@extends('layouts.app')
@section('title', 'مركز النشاط')
@section('sidebar')@include('partials.sidebar-admin')@endsection

@section('content')
<div class="card p-3">
    <div class="d-flex justify-content-between mb-3">
        <h6 class="mb-0">كل الأنشطة — بما فيها كل إجراءات المحاسبين (تسويات، توريدات...)</h6>
        <a href="{{ route('admin.activity.audit') }}" class="btn btn-sm btn-outline-secondary">سجل التدقيق التفصيلي (Audit Log)</a>
    </div>
    <table class="table table-hover align-middle">
        <thead><tr><th>الوقت</th><th>المستخدم</th><th>الدور</th><th>الوصف</th></tr></thead>
        <tbody>
        @foreach ($activities as $log)
            <tr>
                <td class="small text-muted">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                <td>{{ $log->actor?->name ?? 'النظام' }}</td>
                <td><span class="badge bg-light text-dark">{{ $log->actor_role }}</span></td>
                <td>{{ $log->description }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $activities->links() }}
</div>
@endsection
