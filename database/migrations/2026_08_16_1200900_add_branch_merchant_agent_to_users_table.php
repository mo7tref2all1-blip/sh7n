<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('user_type')
                ->constrained()->nullOnDelete();
            $table->foreignId('merchant_id')->nullable()->after('branch_id')
                ->constrained()->nullOnDelete();
            $table->foreignId('agent_id')->nullable()->after('merchant_id')
                ->constrained()->nullOnDelete();
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->foreign('manager_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('merchant_id');
            $table->dropConstrainedForeignId('agent_id');
        });
    }
};
