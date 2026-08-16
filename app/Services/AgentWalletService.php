<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentWalletTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AgentWalletService
{
    public function credit(Agent $agent, string $type, float $amount, ?int $shipmentId, string $description, ?User $actor = null): AgentWalletTransaction
    {
        return $this->write($agent, $type, abs($amount), $shipmentId, $description, $actor);
    }

    public function debit(Agent $agent, string $type, float $amount, ?int $shipmentId, string $description, ?User $actor = null): AgentWalletTransaction
    {
        return $this->write($agent, $type, -abs($amount), $shipmentId, $description, $actor);
    }

    private function write(Agent $agent, string $type, float $signedAmount, ?int $shipmentId, string $description, ?User $actor): AgentWalletTransaction
    {
        return DB::transaction(function () use ($agent, $type, $signedAmount, $shipmentId, $description, $actor) {
            $wallet = $agent->wallet()->lockForUpdate()->firstOrCreate([], ['current_balance' => 0]);

            $newBalance = (float) $wallet->current_balance + $signedAmount;
            $wallet->update(['current_balance' => $newBalance]);

            return $wallet->transactions()->create([
                'type' => $type,
                'amount' => $signedAmount,
                'shipment_id' => $shipmentId,
                'balance_after' => $newBalance,
                'description' => $description,
                'created_by' => $actor?->id,
                'created_at' => now(),
            ]);
        });
    }

    /** Commission earned by the agent for a successfully delivered shipment. */
    public function commissionFor(Agent $agent, float $shippingFee): float
    {
        return $agent->commission_type === 'percentage'
            ? round($shippingFee * ((float) $agent->commission_value / 100), 2)
            : (float) $agent->commission_value;
    }
}
