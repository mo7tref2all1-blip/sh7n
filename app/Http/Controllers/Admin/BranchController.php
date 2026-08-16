<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Governorate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(): View
    {
        $branches = Branch::with('governorate', 'manager')->withCount('custodyShipments')->get();
        $governorates = Governorate::orderBy('name_ar')->get();

        return view('admin.branches.index', compact('branches', 'governorates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20', 'unique:branches,code'],
            'governorate_id' => ['required', 'exists:governorates,id'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $branch = Branch::create($data);
        ActivityLog::record($request->user(), 'created', Branch::class, $branch->id, "إنشاء فرع جديد: {$branch->name}");

        return back()->with('success', 'تم إنشاء الفرع بنجاح.');
    }
}
