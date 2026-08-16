<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipment_id')->constrained()->restrictOnDelete();
            $table->decimal('amount_included', 10, 2);
            $table->timestamps();

            $table->unique(['settlement_id', 'shipment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_items');
    }
};
