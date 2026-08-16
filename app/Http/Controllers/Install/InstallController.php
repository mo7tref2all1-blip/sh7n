<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PDOException;
use Spatie\Permission\Models\Role;

/**
 * Browser-based installer for cPanel accounts with no SSH/Terminal access
 * (docs/08 § 8.2.5). Every step re-derives its own state from the filesystem/DB
 * instead of relying on PHP sessions, so nothing breaks if the visitor's session
 * cookie doesn't survive between requests during setup.
 *
 * Gated by a one-time token shipped as INSTALL-TOKEN.txt alongside the app (never
 * printed on any page) and permanently locked shut by EnsureNotInstalled once
 * storage/installed.lock exists.
 */
class InstallController extends Controller
{
    private const REQUIRED_EXTENSIONS = [
        'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo', 'gd', 'curl', 'zip',
    ];

    public function welcome(): View
    {
        return view('install.welcome', [
            'phpOk' => version_compare(PHP_VERSION, '8.3.0', '>='),
            'extensions' => collect(self::REQUIRED_EXTENSIONS)->mapWithKeys(fn ($ext) => [$ext => extension_loaded($ext)]),
            'envWritable' => is_writable(base_path('.env')),
            'storageWritable' => is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')),
            'tokenFileExists' => File::exists(base_path('INSTALL-TOKEN.txt')),
        ]);
    }

    public function storeDatabase(Request $request): RedirectResponse
    {
        $this->assertToken($request->input('token', ''));

        $data = $request->validate([
            'db_host' => ['required', 'string'],
            'db_port' => ['required', 'string'],
            'db_database' => ['required', 'string'],
            'db_username' => ['required', 'string'],
            'db_password' => ['nullable', 'string'],
            'app_url' => ['required', 'url'],
        ]);

        // Validate the credentials actually connect *before* touching .env, so a typo
        // doesn't leave the app half-configured. Connects straight to the target
        // database rather than trying to CREATE it — on cPanel the DB user is normally
        // scoped to a single already-created database and can't create new ones.
        try {
            new \PDO(
                "mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_database']};charset=utf8mb4",
                $data['db_username'],
                $data['db_password'] ?? ''
            );
        } catch (PDOException $e) {
            return back()->withInput()->withErrors([
                'db_connection' => 'تعذّر الاتصال بقاعدة البيانات — تأكد أنها أُنشئت بالفعل من cPanel > MySQL Databases وأن المستخدم له كل الصلاحيات عليها. الخطأ: '.$e->getMessage(),
            ]);
        }

        $this->setEnv([
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => $data['app_url'],
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => $data['db_password'] ?? '',
        ]);

        return redirect()->route('install.migrate', ['token' => $request->input('token')]);
    }

    public function migrate(Request $request): View|RedirectResponse
    {
        $this->assertToken($request->query('token', ''));

        try {
            DB::purge('mysql');
            DB::connection('mysql')->getPdo();
        } catch (PDOException $e) {
            return redirect()->route('install.welcome')->withErrors(['db_connection' => 'إعداد قاعدة البيانات غير مكتمل: '.$e->getMessage()]);
        }

        Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = Artisan::output();

        Artisan::call('db:seed', ['--class' => \Database\Seeders\GovernorateSeeder::class, '--force' => true]);
        Artisan::call('db:seed', ['--class' => \Database\Seeders\RolePermissionSeeder::class, '--force' => true]);
        Artisan::call('db:seed', ['--class' => \Database\Seeders\PricingSeeder::class, '--force' => true]);

        try {
            Artisan::call('storage:link');
        } catch (\Throwable $e) {
            // Some hosts disable symlink() — not fatal, just means public/storage
            // needs to be created manually later (docs/08).
        }

        return view('install.migrate', [
            'output' => $migrateOutput,
            'token' => $request->query('token'),
        ]);
    }

    public function showAdminForm(Request $request): View|RedirectResponse
    {
        $this->assertToken($request->query('token', ''));

        if (! Schema::hasTable('roles') || ! Role::where('name', User::TYPE_SUPER_ADMIN)->exists()) {
            return redirect()->route('install.welcome')->withErrors(['db_connection' => 'يجب إكمال خطوة قاعدة البيانات أولاً.']);
        }

        return view('install.admin', ['token' => $request->query('token')]);
    }

    public function storeAdmin(Request $request): View|RedirectResponse
    {
        $this->assertToken($request->input('token', ''));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Artisan::call('app:create-admin', [
            '--name' => $data['name'],
            '--phone' => $data['phone'],
            '--email' => $data['email'] ?? '',
            '--password' => $data['password'],
        ]);

        File::put(storage_path('installed.lock'), now()->toDateTimeString());
        File::delete(base_path('INSTALL-TOKEN.txt'));

        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        return view('install.done');
    }

    private function assertToken(string $submitted): void
    {
        $tokenFile = base_path('INSTALL-TOKEN.txt');

        if (! File::exists($tokenFile)) {
            abort(500, 'ملف INSTALL-TOKEN.txt غير موجود في جذر المشروع — لا يمكن إكمال التثبيت بدونه.');
        }

        $expected = trim(File::get($tokenFile));

        if ($expected === '' || ! hash_equals($expected, trim($submitted))) {
            throw ValidationException::withMessages(['token' => 'رمز التثبيت غير صحيح — راجع ملف INSTALL-TOKEN.txt في جذر المشروع.']);
        }
    }

    private function setEnv(array $values): void
    {
        $path = base_path('.env');
        $content = File::get($path);

        foreach ($values as $key => $value) {
            $escaped = str_contains($value, ' ') ? '"'.$value.'"' : $value;
            $pattern = '/^'.preg_quote($key, '/').'=.*/m';
            $line = "{$key}={$escaped}";

            // preg_replace's replacement string treats $ and \ specially (backreferences) —
            // use a callback instead so passwords containing them survive untouched.
            $content = preg_match($pattern, $content)
                ? preg_replace_callback($pattern, fn () => $line, $content)
                : $content."\n{$line}";
        }

        File::put($path, $content);
    }
}
