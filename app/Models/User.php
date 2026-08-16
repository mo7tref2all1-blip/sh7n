<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    public const TYPE_SUPER_ADMIN = 'super_admin';

    public const TYPE_COMPANY_OWNER = 'company_owner';

    public const TYPE_OPS_MANAGER = 'ops_manager';

    public const TYPE_BRANCH_MANAGER = 'branch_manager';

    public const TYPE_CUSTOMER_SERVICE = 'customer_service';

    public const TYPE_ACCOUNTANT = 'accountant';

    public const TYPE_MERCHANT = 'merchant';

    public const TYPE_MERCHANT_EMPLOYEE = 'merchant_employee';

    public const TYPE_AGENT = 'agent';

    public const TYPE_DRIVER = 'driver';

    public const TYPE_WAREHOUSE_EMPLOYEE = 'warehouse_employee';

    /** Roles that belong to a single "back office" admin area (docs/07 § 7.2). */
    public const ADMIN_TYPES = [
        self::TYPE_SUPER_ADMIN,
        self::TYPE_COMPANY_OWNER,
        self::TYPE_OPS_MANAGER,
        self::TYPE_CUSTOMER_SERVICE,
        self::TYPE_ACCOUNTANT,
    ];

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'user_type',
        'branch_id',
        'merchant_id',
        'agent_id',
        'is_active',
        'cash_limit',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'cash_limit' => 'decimal:2',
            'last_login_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function assignedShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'assigned_driver_id');
    }

    public function isDriver(): bool
    {
        return $this->user_type === self::TYPE_DRIVER;
    }

    public function isMerchantUser(): bool
    {
        return in_array($this->user_type, [self::TYPE_MERCHANT, self::TYPE_MERCHANT_EMPLOYEE], true);
    }

    public function isAgentUser(): bool
    {
        return $this->user_type === self::TYPE_AGENT;
    }

    public function dashboardRoute(): string
    {
        return match ($this->user_type) {
            self::TYPE_MERCHANT, self::TYPE_MERCHANT_EMPLOYEE => 'merchant.dashboard',
            self::TYPE_AGENT => 'agent.dashboard',
            self::TYPE_DRIVER => 'driver.dashboard',
            self::TYPE_BRANCH_MANAGER, self::TYPE_WAREHOUSE_EMPLOYEE => 'branch.dashboard',
            self::TYPE_ACCOUNTANT => 'accountant.dashboard',
            default => 'admin.dashboard',
        };
    }
}
