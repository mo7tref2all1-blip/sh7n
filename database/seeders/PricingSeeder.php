<?php

namespace Database\Seeders;

use App\Models\Governorate;
use App\Models\PricingPlan;
use App\Models\PricingRule;
use Illuminate\Database\Seeder;

class PricingSeeder extends Seeder
{
    /** Governorates considered "Greater Cairo" — cheaper/faster than the rest of the country. */
    private const GREATER_CAIRO_CODES = ['CAI', 'GIZ', 'QLY'];

    public function run(): void
    {
        $plans = [
            ['slug' => 'silver', 'name' => 'الباقة الفضية', 'description' => 'الأسعار القياسية'],
            ['slug' => 'gold', 'name' => 'الباقة الذهبية', 'description' => 'أسعار مخفضة لأصحاب الحجم الكبير'],
            ['slug' => 'platinum', 'name' => 'الباقة البلاتينية', 'description' => 'أفضل الأسعار لكبار التجار'],
        ];

        foreach ($plans as $plan) {
            PricingPlan::firstOrCreate(['slug' => $plan['slug']], $plan + ['is_active' => true]);
        }

        $silver = PricingPlan::where('slug', 'silver')->first();
        $gold = PricingPlan::where('slug', 'gold')->first();
        $platinum = PricingPlan::where('slug', 'platinum')->first();

        // Default (plan-less) general rule + one rule per plan, per governorate.
        foreach (Governorate::all() as $governorate) {
            $isCairo = in_array($governorate->code, self::GREATER_CAIRO_CODES, true);
            $basePrice = $isCairo ? 50 : 70;
            $baseReturn = $isCairo ? 25 : 35;

            $this->rule(null, $governorate->id, $basePrice, $baseReturn);
            $this->rule($silver->id, $governorate->id, $basePrice, $baseReturn);
            $this->rule($gold->id, $governorate->id, $basePrice - 5, $baseReturn - 5);
            $this->rule($platinum->id, $governorate->id, $basePrice - 10, $baseReturn - 10);
        }
    }

    private function rule(?int $planId, int $governorateId, float $price, float $returnPrice): void
    {
        PricingRule::firstOrCreate(
            [
                'pricing_plan_id' => $planId,
                'governorate_id' => $governorateId,
                'zone_id' => null,
                'service_type' => 'normal',
            ],
            [
                'weight_from' => 0,
                'weight_to' => 999,
                'price' => max($price, 10),
                'return_price' => max($returnPrice, 5),
            ]
        );
    }
}
