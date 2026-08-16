<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_pod', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('photo_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('gps_lat', 10, 7);
            $table->decimal('gps_lng', 10, 7);
            $table->timestamp('recorded_at')->useCurrent();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_pod');
    }
};
