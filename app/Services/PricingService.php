<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantPricing;
use App\Models\PricingRule;

/**
 * Calculates a shipment's shipping/return fee.
 *
 * Lookup order (docs/02-SRS.md § 2.2.13, docs/03-Database-Design-ERD.md § 3.2.9):
 *   1. merchant_pricing (per-merchant override) — zone-level match preferred over governorate-only.
 *   2. pricing_rules tied to the merchant's assigned pricing_plan.
 *   3. pricing_rules with no plan (the general/default rule).
 */
class PricingService
{
    /**
     * @return array{shipping_fee: float, return_fee: float}
     */
    public function calculate(Merchant $merchant, int $governorateId, ?int $zoneId, float $weightKg, string $serviceType): array
    {
        $merchantRule = $this->findMerchantOverride($merchant->id, $governorateId, $zoneId, $weightKg, $serviceType);
        if ($merchantRule) {
            return ['shipping_fee' => (float) $merchantRule->price, 'return_fee' => (float) $merchantRule->return_price];
        }

        $planRule = $this->findPlanRule($merchant->pricing_plan_id, $governorateId, $zoneId, $weightKg, $serviceType);
        if ($planRule) {
            return ['shipping_fee' => (float) $planRule->price, 'return_fee' => (float) $planRule->return_price];
        }

        $defaultRule = $this->findPlanRule(null, $governorateId, $zoneId, $weightKg, $serviceType);
        if ($defaultRule) {
            return ['shipping_fee' => (float) $defaultRule->price, 'return_fee' => (float) $defaultRule->return_price];
        }

        throw new \RuntimeException(
            "لا يوجد سعر شحن معرَّف لهذه المحافظة/المنطقة (governorate_id={$governorateId}). يجب على الإدارة إضافة قاعدة تسعير أولاً."
        );
    }

    private function findMerchantOverride(int $merchantId, int $governorateId, ?int $zoneId, float $weightKg, string $serviceType): ?MerchantPricing
    {
        $query = MerchantPricing::query()
            ->where('merchant_id', $merchantId)
            ->where('governorate_id', $governorateId)
            ->where('service_type', $serviceType)
            ->where('weight_from', '<=', $weightKg)
            ->where('weight_to', '>=', $weightKg);

        return (clone $query)->where('zone_id', $zoneId)->first()
            ?? $query->whereNull('zone_id')->first();
    }

    private function findPlanRule(?int $pricingPlanId, int $governorateId, ?int $zoneId, float $weightKg, string $serviceType): ?PricingRule
    {
        $query = PricingRule::query()
            ->where(fn ($q) => $pricingPlanId ? $q->where('pricing_plan_id', $pricingPlanId) : $q->whereNull('pricing_plan_id'))
            ->where('governorate_id', $governorateId)
            ->where('service_type', $serviceType)
            ->where('weight_from', '<=', $weightKg)
            ->where('weight_to', '>=', $weightKg);

        return (clone $query)->where('zone_id', $zoneId)->first()
            ?? $query->whereNull('zone_id')->first();
    }
}
