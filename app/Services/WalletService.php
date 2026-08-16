<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

/**
 * The merchant wallet is an append-only ledger. current_balance is a cached snapshot
 * kept in sync on every write. A shipping fee is debited the moment a shipment is
 * created (the merchant owes it regardless of who ends up paying for it — even if the
 * merchant advertises "free shipping" to their own customer, docs/01 & docs/09 make this
 * explicit). A collection is only credited once the cash has actually reached the
 * company (CashService::confirmHandover), never at the moment the driver merely
 * collects it from the customer — that is the "settleable vs informational" split.
 */
class WalletService
{
    public function debit(Merchant $merchant, string $type, float $amount, ?int $shipmentId, string $description, ?User $actor = null): WalletTransaction
    {
        return $this->write($merchant, $type, -abs($amount), $shipmentId, null, $description, $actor);
    }

    public function credit(Merchant $merchant, string $type, float $amount, ?int $shipmentId, string $description, ?User $actor = null): WalletTransaction
    {
        return $this->write($merchant, $type, abs($amount), $shipmentId, null, $description, $actor);
    }

    public function payout(Merchant $merchant, float $amount, int $settlementId, string $description, ?User $actor = null): WalletTransaction
    {
        return $this->write($merchant, WalletTransaction::TYPE_SETTLEMENT_PAYOUT, -abs($amount), null, $settlementId, $description, $actor);
    }

    private function write(Merchant $merchant, string $type, float $signedAmount, ?int $shipmentId, ?int $settlementId, string $description, ?User $actor): WalletTransaction
    {
        return DB::transaction(function () use ($merchant, $type, $signedAmount, $shipmentId, $settlementId, $description, $actor) {
            $wallet = $merchant->wallet()->lockForUpdate()->firstOrCreate([], ['current_balance' => 0]);

            $newBalance = (float) $wallet->current_balance + $signedAmount;
            $wallet->update(['current_balance' => $newBalance]);

            return $wallet->transactions()->create([
                'type' => $type,
                'amount' => $signedAmount,
                'shipment_id' => $shipmentId,
                'settlement_id' => $settlementId,
                'balance_after' => $newBalance,
                'description' => $description,
                'created_by' => $actor?->id,
                'created_at' => now(),
            ]);
        });
    }
}
