<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number', 30)->unique();
            $table->foreignId('merchant_id')->constrained()->restrictOnDelete();
            $table->string('reference_number', 100)->nullable();

            $table->string('consignee_name', 150);
            $table->string('consignee_phone', 20);
            $table->string('consignee_phone_alt', 20)->nullable();
            $table->foreignId('governorate_id')->constrained()->restrictOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->text('address_text');
            $table->decimal('gps_lat', 10, 7)->nullable();
            $table->decimal('gps_lng', 10, 7)->nullable();

            $table->string('package_description', 255)->nullable();
            $table->decimal('weight_kg', 6, 2)->default(1);
            $table->string('service_type', 15)->default('normal'); // normal, express, same_day

            $table->string('payment_method', 20)->default('cod');
            // cod, prepaid, visa_on_delivery, bank_transfer, wallet_payment
            $table->boolean('collection_required')->default(true);
            $table->decimal('amount_to_collect', 10, 2)->default(0);
            $table->decimal('amount_collected', 10, 2)->default(0);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('return_fee', 10, 2)->default(0);

            $table->string('shipment_status', 40)->default('created');
            // see docs/05-Workflows.md for the full state list
            $table->string('financial_status', 20)->default('uncollected');
            // uncollected, collected, received_by_branch, received_by_company, settled_to_merchant
            // NOTE: "collected" is informational only (shown to the merchant the instant the
            // driver/agent takes the cash from the customer). The amount only becomes part of
            // the merchant's *settleable* balance once it reaches received_by_company. Until
            // then it is tracked as a debt against the driver/agent in cash_ledger.

            $table->string('custody_type', 15)->nullable(); // branch, driver, agent, warehouse
            $table->unsignedBigInteger('custody_id')->nullable();
            $table->foreignId('current_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('assigned_driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_agent_id')->nullable()->constrained('agents')->nullOnDelete();

            $table->unsignedTinyInteger('delivery_attempts_count')->default(0);
            $table->date('scheduled_delivery_date')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->boolean('is_return_trip')->default(false);

            $table->string('barcode_value', 64)->nullable();
            $table->string('qr_value', 64)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['merchant_id', 'shipment_status']);
            $table->index(['custody_type', 'custody_id']);
            $table->index(['financial_status', 'shipment_status']);
            $table->index('consignee_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
