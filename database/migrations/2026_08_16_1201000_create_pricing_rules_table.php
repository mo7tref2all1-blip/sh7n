<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_plan_id')->nullable()->constrained()->cascadeOnDelete();
            // null pricing_plan_id = general/default rule
            $table->foreignId('governorate_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('weight_from', 6, 2)->default(0);
            $table->decimal('weight_to', 6, 2)->default(999);
            $table->string('service_type', 15)->default('normal'); // normal, express, same_day
            $table->decimal('price', 8, 2);
            $table->decimal('return_price', 8, 2)->default(0);
            $table->timestamps();

            $table->index(['pricing_plan_id', 'governorate_id', 'zone_id', 'service_type'], 'pricing_rules_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
