<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Governorate;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\PricingPlan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class MerchantController extends Controller
{
    public function index(): View
    {
        $merchants = Merchant::withCount('shipments')->with('wallet')->latest()->paginate(20);

        return view('admin.merchants.index', compact('merchants'));
    }

    public function create(): View
    {
        $governorates = Governorate::orderBy('name_ar')->get();
        $plans = PricingPlan::where('is_active', true)->get();

        return view('admin.merchants.create', compact('governorates', 'plans'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:150'],
            'owner_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20', 'unique:merchants,phone'],
            'email' => ['nullable', 'email'],
            'governorate_id' => ['required', 'exists:governorates,id'],
            'pricing_plan_id' => ['nullable', 'exists:pricing_plans,id'],
            'return_pricing_plan_id' => ['nullable', 'exists:pricing_plans,id'],
            'pod_photo_required' => ['required', 'in:required,optional,disabled'],
            'pod_signature_required' => ['required', 'in:required,optional,disabled'],
            'free_returns' => ['nullable', 'boolean'],
            'return_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'owner_login_password' => ['required', 'string', 'min:6'],
        ]);

        $merchant = DB::transaction(function () use ($data, $request) {
            $merchant = Merchant::create([
                ...collect($data)->except(['owner_login_password'])->toArray(),
                'status' => Merchant::STATUS_ACTIVE,
                'free_returns' => $request->boolean('free_returns'),
            ]);

            $user = User::create([
                'name' => $data['owner_name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => Hash::make($data['owner_login_password']),
                'user_type' => User::TYPE_MERCHANT,
                'merchant_id' => $merchant->id,
            ]);
            $user->syncRoles([User::TYPE_MERCHANT]);

            MerchantUser::create(['merchant_id' => $merchant->id, 'user_id' => $user->id, 'role_in_merchant' => 'owner']);

            return $merchant;
        });

        ActivityLog::record($request->user(), 'created', Merchant::class, $merchant->id, "إنشاء تاجر جديد: {$merchant->business_name}");

        return redirect()->route('admin.merchants.show', $merchant)->with('success', 'تم إنشاء التاجر وحساب الدخول الخاص به بنجاح.');
    }

    public function show(Merchant $merchant): View
    {
        $merchant->load(['wallet.transactions', 'customPricing.governorate', 'customPricing.zone', 'pricingPlan', 'returnPricingPlan']);
        $plans = PricingPlan::where('is_active', true)->get();

        return view('admin.merchants.show', compact('merchant', 'plans'));
    }

    public function update(Request $request, Merchant $merchant): RedirectResponse
    {
        $data = $request->validate([
            'pricing_plan_id' => ['nullable', 'exists:pricing_plans,id'],
            'return_pricing_plan_id' => ['nullable', 'exists:pricing_plans,id'],
            'pod_photo_required' => ['required', 'in:required,optional,disabled'],
            'pod_signature_required' => ['required', 'in:required,optional,disabled'],
            'free_returns' => ['nullable', 'boolean'],
            'return_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'max_delivery_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'status' => ['required', 'in:pending,active,suspended'],
        ]);
        $data['free_returns'] = $request->boolean('free_returns');

        $merchant->update($data);

        ActivityLog::record($request->user(), 'updated', Merchant::class, $merchant->id, "تحديث إعدادات التسعير/المرتجعات/POD للتاجر {$merchant->business_name}");

        return back()->with('success', 'تم تحديث إعدادات التاجر.');
    }
}
