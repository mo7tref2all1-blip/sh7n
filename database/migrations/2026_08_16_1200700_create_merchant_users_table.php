<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role_in_merchant', 20)->default('employee'); // owner, employee
            $table->json('permissions_scope')->nullable();
            $table->timestamps();
            $table->unique(['merchant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_users');
    }
};
