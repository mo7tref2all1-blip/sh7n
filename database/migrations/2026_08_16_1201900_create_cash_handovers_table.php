<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_handovers', function (Blueprint $table) {
            $table->id();
            $table->string('handover_number', 30)->unique();
            $table->string('from_type', 15); // driver, branch, agent
            $table->unsignedBigInteger('from_id');
            $table->string('to_type', 15); // branch, company (accountant)
            $table->unsignedBigInteger('to_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('status', 15)->default('pending'); // pending, confirmed
            $table->foreignId('handed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handed_at')->useCurrent();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('receipt_attachment_path')->nullable();
            $table->timestamps();

            $table->index(['from_type', 'from_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_handovers');
    }
};
