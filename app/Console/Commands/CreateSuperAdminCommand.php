<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Creates the first real Super Admin account. Kept separate from the seeders on
 * purpose: DatabaseSeeder's DemoDataSeeder is for local/dev use only and ships with
 * known weak passwords — production installs (deploy/cpanel-install.sh) must never
 * rely on it. This is the one supported way to get a real admin account in place.
 */
class CreateSuperAdminCommand extends Command
{
    protected $signature = 'app:create-admin
        {--name= : Full name}
        {--phone= : Login phone number}
        {--email= : Email (optional)}
        {--password= : Password (prompted if omitted)}';

    protected $description = 'Create (or promote) the first Super Admin account';

    public function handle(): int
    {
        // Options are checked with !== null (not truthy) so that a programmatic caller
        // (e.g. the web installer via Artisan::call, which always passes every option —
        // even an intentionally blank --email) never falls through to an interactive
        // ask()/confirm(), which would otherwise hang waiting for input that can't come.
        $calledProgrammatically = $this->option('name') !== null;

        $name = $this->option('name') ?? $this->ask('اسم المدير');
        $phone = $this->option('phone') ?? $this->ask('رقم الهاتف (للدخول)');
        $email = $this->option('email') !== null ? $this->option('email') : $this->ask('البريد الإلكتروني (اختياري)', '');
        $password = $this->option('password') ?? $this->secret('كلمة المرور');

        $validator = Validator::make(
            compact('name', 'phone', 'email', 'password'),
            [
                'name' => ['required', 'string', 'max:150'],
                'phone' => ['required', 'string', 'max:20'],
                'email' => ['nullable', 'email'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $existing = User::where('phone', $phone)->first();

        if ($existing) {
            $shouldPromote = $calledProgrammatically
                || $this->confirm("يوجد مستخدم بهذا الرقم بالفعل ({$existing->name}) — هل تريد ترقيته لـ Super Admin وتحديث كلمة المرور؟");

            if (! $shouldPromote) {
                return self::FAILURE;
            }

            $existing->forceFill([
                'name' => $name,
                'email' => $email ?: $existing->email,
                'password' => Hash::make($password),
                'user_type' => User::TYPE_SUPER_ADMIN,
                'is_active' => true,
            ])->save();
            $existing->syncRoles([User::TYPE_SUPER_ADMIN]);

            $this->info("تم ترقية {$existing->name} إلى Super Admin.");

            return self::SUCCESS;
        }

        $user = User::create([
            'name' => $name,
            'phone' => $phone,
            'email' => $email ?: null,
            'password' => Hash::make($password),
            'user_type' => User::TYPE_SUPER_ADMIN,
            'is_active' => true,
        ]);
        $user->syncRoles([User::TYPE_SUPER_ADMIN]);

        $this->info("تم إنشاء حساب Super Admin بنجاح: {$user->name} ({$user->phone}).");

        return self::SUCCESS;
    }
}
