<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\CashHandover;
use App\Models\Merchant;
use App\Models\Settlement;
use App\Services\CashService;
use App\Services\SettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettlementController extends Controller
{
    public function dashboard(): View
    {
        $merchants = Merchant::with('wallet')->get()->sortByDesc(fn ($m) => (float) ($m->wallet->current_balance ?? 0))->values();

        $pendingHandovers = CashHandover::where('to_type', 'company')->where('status', CashHandover::STATUS_PENDING)->count();

        return view('accountant.dashboard', compact('merchants', 'pendingHandovers'));
    }

    public function index(): View
    {
        $settlements = Settlement::with('merchant')->latest()->paginate(20);

        return view('accountant.settlements.index', compact('settlements'));
    }

    public function store(Request $request, Merchant $merchant, SettlementService $settlements): RedirectResponse
    {
        $data = $request->validate(['period_from' => ['nullable', 'date'], 'period_to' => ['nullable', 'date']]);

        $settlement = $settlements->createDraft($merchant, $data['period_from'] ?? null, $data['period_to'] ?? null, $request->user());

        return redirect()->route('accountant.settlements.index')->with('success', "تم إنشاء تسوية {$settlement->settlement_number}.");
    }

    public function approve(Request $request, Settlement $settlement, SettlementService $settlements): RedirectResponse
    {
        $settlements->approve($settlement, $request->user());

        return back()->with('success', 'تم اعتماد التسوية.');
    }

    public function pay(Request $request, Settlement $settlement, SettlementService $settlements): RedirectResponse
    {
        $request->validate(['proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120']]);

        $path = $request->file('proof')->store('settlement-proofs', 'public');
        $settlements->markPaid($settlement, $request->user(), $path);

        return back()->with('success', 'تم تسجيل دفع التسوية وإرفاق إثبات التحويل — أصبح ظاهرًا للتاجر.');
    }

    public function handovers(): View
    {
        $handovers = CashHandover::where('to_type', 'company')->where('status', CashHandover::STATUS_PENDING)
            ->with('handedBy')->latest('handed_at')->get();

        return view('accountant.handovers.index', compact('handovers'));
    }

    public function confirmHandover(Request $request, CashHandover $handover, CashService $cash): RedirectResponse
    {
        $cash->confirmHandover($handover, $request->user());

        return back()->with('success', 'تم تأكيد استلام التوريد — أصبحت الشحنات المرتبطة قابلة للتسوية.');
    }
}
