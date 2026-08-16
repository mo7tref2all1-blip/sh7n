<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Branch;
use App\Models\Governorate;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\PricingPlan;
use App\Models\User;
use App\Services\CashService;
use App\Services\ShipmentLifecycleService;
use Illuminate\Database\Seeder;

/** Demo accounts + a couple of sample shipments so the whole lifecycle can be clicked through. */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $cairo = Governorate::where('code', 'CAI')->first();
        $giza = Governorate::where('code', 'GIZ')->first();

        $branch = Branch::firstOrCreate(
            ['code' => 'CAI-MAIN'],
            ['name' => 'الفرع الرئيسي - القاهرة', 'governorate_id' => $cairo->id, 'address' => 'مدينة نصر، القاهرة', 'phone' => '0221234567']
        );

        $superAdmin = $this->user('مدير النظام', '01000000001', 'admin@example.com', User::TYPE_SUPER_ADMIN, ['branch_id' => $branch->id]);
        $owner = $this->user('صاحب الشركة', '01000000002', 'owner@example.com', User::TYPE_COMPANY_OWNER);
        $opsManager = $this->user('مدير العمليات', '01000000003', 'ops@example.com', User::TYPE_OPS_MANAGER);
        $branchManager = $this->user('مدير الفرع', '01000000004', 'branch@example.com', User::TYPE_BRANCH_MANAGER, ['branch_id' => $branch->id]);
        $this->user('خدمة العملاء', '01000000005', 'cs@example.com', User::TYPE_CUSTOMER_SERVICE);
        $accountant = $this->user('المحاسب', '01000000006', 'accountant@example.com', User::TYPE_ACCOUNTANT);

        $branch->update(['manager_id' => $branchManager->id]);

        $goldPlan = PricingPlan::where('slug', 'gold')->first();

        $merchant = Merchant::firstOrCreate(
            ['phone' => '01000000010'],
            [
                'business_name' => 'متجر تجريبي',
                'owner_name' => 'تاجر تجريبي',
                'email' => 'merchant@example.com',
                'governorate_id' => $cairo->id,
                'pricing_plan_id' => $goldPlan->id,
                'return_pricing_plan_id' => $goldPlan->id,
                'status' => Merchant::STATUS_ACTIVE,
                'pod_photo_required' => Merchant::POD_OPTIONAL,
                'pod_signature_required' => Merchant::POD_DISABLED,
                'free_returns' => false,
                'return_discount_percent' => 50, // this merchant only bears 50% of the return fee
            ]
        );

        $merchantUser = $this->user('صاحب المتجر التجريبي', '01000000011', 'merchant-owner@example.com', User::TYPE_MERCHANT, ['merchant_id' => $merchant->id]);
        MerchantUser::firstOrCreate(['merchant_id' => $merchant->id, 'user_id' => $merchantUser->id], ['role_in_merchant' => 'owner']);

        $agent = Agent::firstOrCreate(
            ['phone' => '01000000020'],
            ['name' => 'وكيل أسوان', 'governorate_id' => Governorate::where('code', 'ASN')->first()->id, 'commission_type' => 'fixed', 'commission_value' => 10, 'status' => 'active']
        );
        $this->user('مستخدم وكيل أسوان', '01000000021', 'agent@example.com', User::TYPE_AGENT, ['agent_id' => $agent->id]);
        $this->user('مندوب الوكيل - محمود', '01000000022', null, User::TYPE_DRIVER, ['agent_id' => $agent->id]);

        $driver = $this->user('مندوب - أحمد علي', '01000000030', 'driver@example.com', User::TYPE_DRIVER, ['branch_id' => $branch->id, 'cash_limit' => 5000]);

        // --- Sample end-to-end shipment: created -> picked up -> delivered -> cash handover ---
        $lifecycle = app(ShipmentLifecycleService::class);
        $cash = app(CashService::class);

        $shipment = $lifecycle->createShipment($merchant, [
            'consignee_name' => 'عميل تجريبي',
            'consignee_phone' => '01099999999',
            'governorate_id' => $giza->id,
            'address_text' => 'شارع الهرم، الجيزة',
            'package_description' => 'ملابس',
            'weight_kg' => 1.5,
            'service_type' => 'normal',
            'payment_method' => 'cod',
            'amount_to_collect' => 450,
        ], $opsManager);

        $lifecycle->assign($shipment, $driver, null, $opsManager);
        $shipment = $lifecycle->pickup($shipment, $driver, 'driver', $driver->id);
        $shipment = $lifecycle->deliver($shipment, $driver, [
            'amount_collected' => 450,
            'gps_lat' => 30.0444,
            'gps_lng' => 31.2357,
            'notes' => 'تم التسليم بنجاح (بيانات تجريبية)',
        ]);

        // Driver hands the day's cash to the branch, branch confirms, then hands it to the company.
        $handover1 = $cash->initiateHandover('driver', $driver->id, 'branch', $branch->id, 450, $driver);
        $cash->confirmHandover($handover1, $branchManager);
        $handover2 = $cash->initiateHandover('branch', $branch->id, 'company', null, 450, $branchManager);
        $cash->confirmHandover($handover2, $accountant);

        $this->command?->info('Demo data seeded. Sample shipment: '.$shipment->fresh()->tracking_number);
    }

    private function user(string $name, string $phone, ?string $email, string $type, array $extra = []): User
    {
        $user = User::firstOrCreate(
            ['phone' => $phone],
            array_merge([
                'name' => $name,
                'email' => $email,
                'password' => 'password',
                'user_type' => $type,
                'is_active' => true,
            ], $extra)
        );

        $user->syncRoles([$type]);

        return $user;
    }
}
