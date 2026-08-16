<?php

use App\Http\Controllers\Accountant\SettlementController as AccountantSettlementController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MerchantController;
use App\Http\Controllers\Admin\PricingController;
use App\Http\Controllers\Admin\ReconciliationController;
use App\Http\Controllers\Admin\ShipmentController as AdminShipmentController;
use App\Http\Controllers\Agent\DashboardController as AgentDashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Branch\DashboardController as BranchDashboardController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\Driver\ShipmentActionController;
use App\Http\Controllers\Merchant\DashboardController as MerchantDashboardController;
use App\Http\Controllers\Merchant\ShipmentController as MerchantShipmentController;
use App\Http\Controllers\Merchant\WalletController as MerchantWalletController;
use App\Http\Controllers\Public\TrackingController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/track/{trackingNumber}', [TrackingController::class, 'show'])->name('track.show');
Route::post('/track/{trackingNumber}/reschedule', [TrackingController::class, 'reschedule'])->name('track.reschedule');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

$adminTypes = [User::TYPE_SUPER_ADMIN, User::TYPE_COMPANY_OWNER, User::TYPE_OPS_MANAGER, User::TYPE_CUSTOMER_SERVICE];

Route::middleware(['auth', 'user_type:'.implode(',', $adminTypes)])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/shipments', [AdminShipmentController::class, 'index'])->name('shipments.index');
    Route::get('/shipments/{shipment}', [AdminShipmentController::class, 'show'])->name('shipments.show');
    Route::post('/shipments/{shipment}/assign', [AdminShipmentController::class, 'assign'])->name('shipments.assign');
    Route::get('/reconciliation', [ReconciliationController::class, 'index'])->name('reconciliation');
    Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
    Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
    Route::get('/merchants', [MerchantController::class, 'index'])->name('merchants.index');
    Route::get('/merchants/create', [MerchantController::class, 'create'])->name('merchants.create');
    Route::post('/merchants', [MerchantController::class, 'store'])->name('merchants.store');
    Route::get('/merchants/{merchant}', [MerchantController::class, 'show'])->name('merchants.show');
    Route::put('/merchants/{merchant}', [MerchantController::class, 'update'])->name('merchants.update');
    Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');
    Route::post('/agents', [AgentController::class, 'store'])->name('agents.store');
    Route::get('/pricing', [PricingController::class, 'index'])->name('pricing.index');
    Route::post('/pricing/merchant/{merchant}', [PricingController::class, 'storeMerchantRule'])->name('pricing.merchant.store');
    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
    Route::get('/audit', [ActivityController::class, 'audit'])->name('activity.audit');
});

Route::middleware(['auth', 'user_type:'.User::TYPE_ACCOUNTANT])->prefix('accountant')->name('accountant.')->group(function () {
    Route::get('/', [AccountantSettlementController::class, 'dashboard'])->name('dashboard');
    Route::get('/settlements', [AccountantSettlementController::class, 'index'])->name('settlements.index');
    Route::post('/settlements/{merchant}', [AccountantSettlementController::class, 'store'])->name('settlements.store');
    Route::post('/settlements/{settlement}/approve', [AccountantSettlementController::class, 'approve'])->name('settlements.approve');
    Route::post('/settlements/{settlement}/pay', [AccountantSettlementController::class, 'pay'])->name('settlements.pay');
    Route::get('/cash-handovers', [AccountantSettlementController::class, 'handovers'])->name('handovers.index');
    Route::post('/cash-handovers/{handover}/confirm', [AccountantSettlementController::class, 'confirmHandover'])->name('handovers.confirm');
});

Route::middleware(['auth', 'user_type:'.User::TYPE_BRANCH_MANAGER.','.User::TYPE_WAREHOUSE_EMPLOYEE])->prefix('branch')->name('branch.')->group(function () {
    Route::get('/', [BranchDashboardController::class, 'index'])->name('dashboard');
    Route::post('/cash-handovers/{handover}/confirm', [BranchDashboardController::class, 'confirmHandover'])->name('handovers.confirm');
});

Route::middleware(['auth', 'user_type:'.User::TYPE_MERCHANT.','.User::TYPE_MERCHANT_EMPLOYEE])->prefix('merchant')->name('merchant.')->group(function () {
    Route::get('/', [MerchantDashboardController::class, 'index'])->name('dashboard');
    Route::get('/shipments', [MerchantShipmentController::class, 'index'])->name('shipments.index');
    Route::get('/shipments/create', [MerchantShipmentController::class, 'create'])->name('shipments.create');
    Route::post('/shipments', [MerchantShipmentController::class, 'store'])->name('shipments.store');
    Route::get('/shipments/{shipment}', [MerchantShipmentController::class, 'show'])->name('shipments.show');
    Route::get('/shipments-import', [MerchantShipmentController::class, 'showImport'])->name('shipments.import.show');
    Route::post('/shipments-import', [MerchantShipmentController::class, 'import'])->name('shipments.import');
    Route::get('/wallet', [MerchantWalletController::class, 'index'])->name('wallet.index');
    Route::get('/settlements', [MerchantWalletController::class, 'settlements'])->name('settlements.index');
});

Route::middleware(['auth', 'user_type:'.User::TYPE_AGENT])->prefix('agent')->name('agent.')->group(function () {
    Route::get('/', [AgentDashboardController::class, 'index'])->name('dashboard');
    Route::post('/shipments/{shipment}/assign-driver', [AgentDashboardController::class, 'assignDriver'])->name('shipments.assign_driver');
    Route::post('/cash-handovers', [AgentDashboardController::class, 'handover'])->name('handovers.store');
});

Route::middleware(['auth', 'user_type:'.User::TYPE_DRIVER])->prefix('driver')->name('driver.')->group(function () {
    Route::get('/', [DriverDashboardController::class, 'index'])->name('dashboard');
    Route::get('/shipments/{shipment}', [DriverDashboardController::class, 'show'])->name('shipments.show');
    Route::post('/shipments/{shipment}/pickup', [ShipmentActionController::class, 'pickup'])->name('shipments.pickup');
    Route::post('/shipments/{shipment}/deliver', [ShipmentActionController::class, 'deliver'])->name('shipments.deliver');
    Route::post('/shipments/{shipment}/fail', [ShipmentActionController::class, 'fail'])->name('shipments.fail');
    Route::post('/cash-handovers', [ShipmentActionController::class, 'handover'])->name('handovers.store');
});
