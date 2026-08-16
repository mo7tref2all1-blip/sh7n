<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_custody_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('from_type', 15)->nullable();
            $table->unsignedBigInteger('from_id')->nullable();
            $table->string('to_type', 15);
            $table->unsignedBigInteger('to_id');
            $table->foreignId('handed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handed_at')->useCurrent();
            $table->timestamp('received_at')->nullable();
            $table->string('status', 15)->default('pending'); // pending, confirmed
            $table->timestamps();

            $table->index(['to_type', 'to_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_custody_log');
    }
};
