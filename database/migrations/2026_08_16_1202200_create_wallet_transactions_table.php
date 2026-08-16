<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('type', 25);
            // credit_collection, debit_shipping_fee, debit_return_fee, settlement_payout, manual_adjustment
            $table->decimal('amount', 10, 2);
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('settlement_id')->nullable();
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['wallet_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
