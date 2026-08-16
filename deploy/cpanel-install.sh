#!/usr/bin/env bash
#
# One-shot installer for the shipping platform on cPanel (shared hosting or VPS with
# cPanel). Run this from an SSH session (or cPanel > Terminal) inside the folder you
# cloned/uploaded the app into. Safe to re-run — every step is idempotent.
#
# See docs/08-System-Architecture-Security-Deployment.md for the full manual walkthrough
# this script automates.
#
# Usage:
#   ./deploy/cpanel-install.sh
#   ./deploy/cpanel-install.sh --db-host=localhost --db-name=user_shipping --db-user=user_ship --db-pass='...' --app-url=https://ship.example.com
#
# Flags (all optional — you'll be prompted for anything missing, unless --non-interactive
# is set, in which case missing DB values abort and the admin-account step is skipped):
#   --db-host=HOST
#   --db-name=NAME
#   --db-user=USER
#   --db-pass=PASS
#   --app-url=URL          e.g. https://ship.example.com
#   --non-interactive       never prompt; fail instead of asking
#   --skip-admin            skip the "create super admin" step
#
set -euo pipefail

# ---------------------------------------------------------------------------
# 0. Setup & helpers
# ---------------------------------------------------------------------------
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$SCRIPT_DIR"

C_GREEN='\033[0;32m'; C_YELLOW='\033[1;33m'; C_RED='\033[0;31m'; C_BLUE='\033[0;34m'; C_RESET='\033[0m'
step()  { echo -e "\n${C_BLUE}==>${C_RESET} $1"; }
ok()    { echo -e "${C_GREEN}✓${C_RESET} $1"; }
warn()  { echo -e "${C_YELLOW}!${C_RESET} $1"; }
fail()  { echo -e "${C_RED}✗ $1${C_RESET}"; exit 1; }

DB_HOST="" DB_NAME="" DB_USER="" DB_PASS="" APP_URL="" NON_INTERACTIVE=0 SKIP_ADMIN=0

for arg in "$@"; do
  case $arg in
    --db-host=*) DB_HOST="${arg#*=}" ;;
    --db-name=*) DB_NAME="${arg#*=}" ;;
    --db-user=*) DB_USER="${arg#*=}" ;;
    --db-pass=*) DB_PASS="${arg#*=}" ;;
    --app-url=*) APP_URL="${arg#*=}" ;;
    --non-interactive) NON_INTERACTIVE=1 ;;
    --skip-admin) SKIP_ADMIN=1 ;;
    *) fail "معطى غير معروف: $arg" ;;
  esac
done

ask() { # ask "prompt" "default"
  local prompt="$1" default="${2:-}" reply
  if [ "$NON_INTERACTIVE" = "1" ]; then
    [ -n "$default" ] && { echo "$default"; return; }
    fail "تشغيل --non-interactive لكن القيمة المطلوبة ناقصة: $prompt"
  fi
  read -r -p "$prompt$( [ -n "$default" ] && echo " [$default]" ): " reply
  echo "${reply:-$default}"
}

echo -e "${C_BLUE}"
echo "============================================================"
echo " تثبيت منصة إدارة الشحن على cPanel"
echo "============================================================"
echo -e "${C_RESET}"

# ---------------------------------------------------------------------------
# 1. Preflight checks
# ---------------------------------------------------------------------------
step "1/9 — فحص البيئة"

command -v php >/dev/null 2>&1 || fail "PHP غير متاح في PATH — فعّله من cPanel > Setup PHP App / MultiPHP Manager أولاً."
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
PHP_MAJOR_MINOR=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
ok "PHP $PHP_VERSION"
awk -v v="$PHP_MAJOR_MINOR" 'BEGIN{split(v,a,"."); if (a[1]<8 || (a[1]==8 && a[2]<3)) exit 1; exit 0}' \
  || fail "يتطلب PHP 8.3 أو أحدث (الحالي: $PHP_VERSION). غيّره من MultiPHP Manager في cPanel."

REQUIRED_EXT="pdo_mysql mbstring openssl tokenizer xml ctype json bcmath fileinfo gd curl zip"
MISSING_EXT=""
for ext in $REQUIRED_EXT; do
  php -m | grep -qi "^${ext}$" || MISSING_EXT="$MISSING_EXT $ext"
done
if [ -n "$MISSING_EXT" ]; then
  fail "إضافات PHP ناقصة:$MISSING_EXT — فعّلها من MultiPHP INI Editor في cPanel."
fi
ok "كل إضافات PHP المطلوبة متوفرة"

COMPOSER_BIN="composer"
if ! command -v composer >/dev/null 2>&1; then
  warn "أمر composer غير متاح — سيتم تحميل composer.phar محليًا"
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  php composer-setup.php --quiet
  rm -f composer-setup.php
  COMPOSER_BIN="php composer.phar"
fi
ok "Composer جاهز"

# ---------------------------------------------------------------------------
# 2. .env
# ---------------------------------------------------------------------------
step "2/9 — إعداد ملف .env"

if [ ! -f .env ]; then
  cp .env.example .env
  ok "تم إنشاء .env من .env.example"
else
  ok ".env موجود بالفعل — لن يُستبدل (سيُحدَّث فقط)"
fi

[ -n "$DB_HOST" ] || DB_HOST=$(ask "MySQL Host" "localhost")
[ -n "$DB_NAME" ] || DB_NAME=$(ask "اسم قاعدة البيانات (من cPanel > MySQL Databases)")
[ -n "$DB_USER" ] || DB_USER=$(ask "مستخدم قاعدة البيانات")
[ -n "$DB_PASS" ] || DB_PASS=$(ask "كلمة مرور قاعدة البيانات")
[ -n "$APP_URL" ] || APP_URL=$(ask "رابط الموقع (APP_URL)" "https://$(hostname -f 2>/dev/null || echo example.com)")

set_env() { # set_env KEY VALUE
  local key="$1" value="$2" escaped
  escaped=$(printf '%s' "$value" | sed -e 's/[\/&]/\\&/g')
  if grep -q "^${key}=" .env; then
    sed -i "s/^${key}=.*/${key}=${escaped}/" .env
  else
    echo "${key}=${value}" >> .env
  fi
}

set_env "APP_ENV" "production"
set_env "APP_DEBUG" "false"
set_env "APP_URL" "$APP_URL"
set_env "DB_CONNECTION" "mysql"
set_env "DB_HOST" "$DB_HOST"
set_env "DB_DATABASE" "$DB_NAME"
set_env "DB_USERNAME" "$DB_USER"
set_env "DB_PASSWORD" "$DB_PASS"
ok "تم ضبط بيانات قاعدة البيانات و APP_URL في .env"

# ---------------------------------------------------------------------------
# 3. Composer dependencies
# ---------------------------------------------------------------------------
step "3/9 — تثبيت حزم Composer (قد يستغرق دقيقة)"
$COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction
ok "تم تثبيت الحزم"

# ---------------------------------------------------------------------------
# 4. App key
# ---------------------------------------------------------------------------
step "4/9 — مفتاح التطبيق"
if grep -q "^APP_KEY=$" .env || ! grep -q "^APP_KEY=base64:" .env; then
  php artisan key:generate --force
  ok "تم توليد APP_KEY"
else
  ok "APP_KEY موجود بالفعل"
fi

# ---------------------------------------------------------------------------
# 5. Database migrations + core seed data (NOT demo/test accounts)
# ---------------------------------------------------------------------------
step "5/9 — تشغيل Migrations وبيانات النظام الأساسية"
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\GovernorateSeeder --force
php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder --force
php artisan db:seed --class=Database\\Seeders\\PricingSeeder --force
ok "تم إنشاء الجداول + المحافظات + الأدوار/الصلاحيات + التسعير الافتراضي"
warn "لم يتم تشغيل DemoDataSeeder عن قصد (يحتوي حسابات تجريبية بكلمة مرور ضعيفة) — لا تستخدمه في الإنتاج."

# ---------------------------------------------------------------------------
# 6. Storage
# ---------------------------------------------------------------------------
step "6/9 — الروابط والصلاحيات"
php artisan storage:link || true
chmod -R ug+rwX storage bootstrap/cache
ok "تم ضبط storage:link والصلاحيات"

# ---------------------------------------------------------------------------
# 7. Caches
# ---------------------------------------------------------------------------
step "7/9 — تحسين الأداء (Cache)"
php artisan config:cache
php artisan route:cache
php artisan view:cache
ok "تم تفعيل Config/Route/View cache"

# ---------------------------------------------------------------------------
# 8. Super admin account
# ---------------------------------------------------------------------------
step "8/9 — إنشاء حساب Super Admin"
if [ "$SKIP_ADMIN" = "1" ]; then
  warn "تم تخطي إنشاء حساب المدير (--skip-admin) — نفّذه لاحقًا بـ: php artisan app:create-admin"
elif [ "$NON_INTERACTIVE" = "1" ]; then
  warn "وضع --non-interactive: تخطي إنشاء حساب المدير — نفّذه لاحقًا بـ: php artisan app:create-admin"
else
  CREATE_ADMIN_REPLY=$(ask "هل تريد إنشاء حساب Super Admin الآن؟ (y/n)" "y")
  case "$CREATE_ADMIN_REPLY" in
    y|Y|yes|Yes) php artisan app:create-admin ;;
    *) warn "تم التخطي — نفّذه لاحقًا بـ: php artisan app:create-admin" ;;
  esac
fi

# ---------------------------------------------------------------------------
# 9. Final instructions
# ---------------------------------------------------------------------------
step "9/9 — خطوات يدوية متبقية (لا يمكن أتمتتها بأمان من هذا السكريبت)"
APP_PATH="$SCRIPT_DIR"
echo -e "${C_YELLOW}"
cat <<EOF

1) وجّه Document Root للدومين/الساب دومين إلى:
   ${APP_PATH}/public
   (من cPanel > Domains، أو Addon Domains حسب إعدادك)

2) أضف Cron Jobs التاليين من cPanel > Cron Jobs (كل دقيقة):
   * * * * * cd ${APP_PATH} && php artisan schedule:run >> /dev/null 2>&1
   * * * * * cd ${APP_PATH} && timeout 55 php artisan queue:work --stop-when-empty --tries=3 --max-time=50 >> /dev/null 2>&1

3) فعّل SSL مجاني (AutoSSL) من cPanel > SSL/TLS Status.

4) راجع docs/08-System-Architecture-Security-Deployment.md للتفاصيل الكاملة
   (النسخ الاحتياطي، خطة التوسع، المراقبة).

EOF
echo -e "${C_RESET}"
ok "التثبيت اكتمل 🎉"
