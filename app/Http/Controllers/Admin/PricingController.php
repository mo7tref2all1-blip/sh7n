<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Governorate;
use App\Models\Merchant;
use App\Models\MerchantPricing;
use App\Models\PricingPlan;
use App\Models\PricingRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(): View
    {
        $plans = PricingPlan::with('rules.governorate')->get();
        $merchants = Merchant::orderBy('business_name')->get();
        $governorates = Governorate::orderBy('name_ar')->get();

        return view('admin.pricing.index', compact('plans', 'merchants', 'governorates'));
    }

    /** Merchant-specific price override — takes priority over any plan rule (docs/02 § FR-121). */
    public function storeMerchantRule(Request $request, Merchant $merchant): RedirectResponse
    {
        $data = $request->validate([
            'governorate_id' => ['required', 'exists:governorates,id'],
            'service_type' => ['required', 'in:normal,express,same_day'],
            'price' => ['required', 'numeric', 'min:0'],
            'return_price' => ['required', 'numeric', 'min:0'],
        ]);

        MerchantPricing::updateOrCreate(
            ['merchant_id' => $merchant->id, 'governorate_id' => $data['governorate_id'], 'zone_id' => null, 'service_type' => $data['service_type']],
            ['weight_from' => 0, 'weight_to' => 999, 'price' => $data['price'], 'return_price' => $data['return_price']]
        );

        ActivityLog::record($request->user(), 'updated', PricingRule::class, null,
            "تعديل تسعير مخصص للتاجر {$merchant->business_name}: {$data['price']} ج.م");

        return back()->with('success', 'تم حفظ التسعير المخصص لهذا التاجر.');
    }
}
