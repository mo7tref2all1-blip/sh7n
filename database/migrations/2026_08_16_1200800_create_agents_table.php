<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('phone', 20);
            $table->foreignId('governorate_id')->constrained()->restrictOnDelete();
            // exclusive distribution governorates (in addition to the primary one above)
            $table->json('exclusive_governorate_ids')->nullable();
            $table->string('commission_type', 15)->default('fixed'); // fixed, percentage
            $table->decimal('commission_value', 8, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
