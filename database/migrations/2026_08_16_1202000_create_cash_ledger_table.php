<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_ledger', function (Blueprint $table) {
            $table->id();
            $table->string('holder_type', 15); // driver, branch, agent
            $table->unsignedBigInteger('holder_id');
            $table->string('entry_type', 20); // collection, handover_out, handover_in, adjustment
            $table->decimal('amount', 10, 2);
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cash_handover_id')->nullable()->constrained('cash_handovers')->nullOnDelete();
            $table->decimal('balance_after', 10, 2)->default(0);
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['holder_type', 'holder_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_ledger');
    }
};
