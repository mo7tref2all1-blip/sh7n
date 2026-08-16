<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\Governorate;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function index(): View
    {
        $agents = Agent::with('governorate', 'wallet')->withCount('shipments', 'drivers')->get();
        $governorates = Governorate::orderBy('name_ar')->get();

        return view('admin.agents.index', compact('agents', 'governorates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20', 'unique:agents,phone'],
            'governorate_id' => ['required', 'exists:governorates,id'],
            'commission_type' => ['required', 'in:fixed,percentage'],
            'commission_value' => ['required', 'numeric', 'min:0'],
            'login_password' => ['required', 'string', 'min:6'],
        ]);

        $agent = DB::transaction(function () use ($data) {
            $agent = Agent::create([...collect($data)->except('login_password')->toArray(), 'status' => 'active']);

            $user = User::create([
                'name' => $data['name'].' (وكيل)',
                'phone' => $data['phone'],
                'password' => Hash::make($data['login_password']),
                'user_type' => User::TYPE_AGENT,
                'agent_id' => $agent->id,
            ]);
            $user->syncRoles([User::TYPE_AGENT]);

            return $agent;
        });

        ActivityLog::record($request->user(), 'created', Agent::class, $agent->id, "إنشاء وكيل شحن جديد: {$agent->name}");

        return back()->with('success', 'تم إنشاء الوكيل وحساب الدخول الخاص به.');
    }
}
