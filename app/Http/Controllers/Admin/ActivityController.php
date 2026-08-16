<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(): View
    {
        $activities = ActivityLog::with('actor')->latest('created_at')->paginate(30);

        return view('admin.activity.index', compact('activities'));
    }

    public function audit(): View
    {
        $logs = AuditLog::with('user')->latest('created_at')->paginate(30);

        return view('admin.activity.audit', compact('logs'));
    }
}
