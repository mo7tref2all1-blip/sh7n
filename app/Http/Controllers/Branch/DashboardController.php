<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\CashHandover;
use App\Models\CashLedger;
use App\Services\CashService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $branch = $request->user()->branch;

        $employees = $branch->employees()->get();
        $pendingHandovers = CashHandover::where('to_type', 'branch')->where('to_id', $branch->id)
            ->where('status', CashHandover::STATUS_PENDING)->with('handedBy')->get();
        $cashInHand = CashLedger::balanceFor('branch', $branch->id);

        return view('branch.dashboard', compact('branch', 'employees', 'pendingHandovers', 'cashInHand'));
    }

    public function confirmHandover(Request $request, CashHandover $handover, CashService $cash): RedirectResponse
    {
        abort_unless($handover->to_type === 'branch' && $handover->to_id === $request->user()->branch_id, 403);

        $cash->confirmHandover($handover, $request->user());

        return back()->with('success', 'تم تأكيد استلام التوريد النقدي.');
    }
}
