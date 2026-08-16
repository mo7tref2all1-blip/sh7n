<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->string('business_name', 150);
            $table->string('owner_name', 150);
            $table->string('phone', 20);
            $table->string('email', 150)->nullable();
            $table->foreignId('governorate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pricing_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('return_pricing_plan_id')->nullable()
                ->constrained('pricing_plans')->nullOnDelete();
            $table->string('status', 20)->default('pending'); // pending, active, suspended

            // POD policy per merchant: required | optional | disabled
            $table->string('pod_photo_required', 15)->default('optional');
            $table->string('pod_signature_required', 15)->default('disabled');

            // Return policy per merchant
            $table->boolean('free_returns')->default(false);
            $table->decimal('return_discount_percent', 5, 2)->default(0);
            // % of return fee the merchant is charged, e.g. 50 = merchant pays 50% of return cost

            $table->unsignedTinyInteger('max_delivery_attempts')->default(3);

            $table->string('api_token', 100)->nullable()->unique();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
