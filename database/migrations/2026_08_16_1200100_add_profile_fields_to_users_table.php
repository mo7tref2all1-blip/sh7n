<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->unique()->after('name');
            $table->string('user_type', 30)->default('driver')->after('phone');
            // super_admin, company_owner, ops_manager, branch_manager, customer_service,
            // accountant, merchant, merchant_employee, agent, driver, warehouse_employee
            $table->boolean('is_active')->default(true)->after('user_type');
            $table->boolean('two_factor_enabled')->default(false)->after('is_active');
            $table->decimal('cash_limit', 10, 2)->nullable()->after('two_factor_enabled');
            $table->timestamp('last_login_at')->nullable()->after('cash_limit');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'user_type', 'is_active', 'two_factor_enabled',
                'cash_limit', 'last_login_at', 'deleted_at',
            ]);
        });
    }
};
