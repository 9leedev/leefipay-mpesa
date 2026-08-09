#!/usr/bin/env bash
# Install the local package into a fresh Laravel app and verify integration.
# Never performs real LeefiPay / M-Pesa / Safaricom HTTP requests.
set -euo pipefail

PACKAGE_DIR="${PACKAGE_DIR:-$(cd "$(dirname "$0")/../.." && pwd)}"
LARAVEL_CONSTRAINT="${LARAVEL_CONSTRAINT:?LARAVEL_CONSTRAINT is required (e.g. 11.*)}"
APP_DIR="${APP_DIR:-${TMPDIR:-/tmp}/leefipay-laravel-app}"

# Isolate Composer config so we can install older Laravel majors that Packagist
# may flag with advisories. These apps are disposable CI fixtures only.
COMPOSER_HOME="${COMPOSER_HOME:-${RUNNER_TEMP:-${TMPDIR:-/tmp}}/leefipay-composer-home}"
mkdir -p "${COMPOSER_HOME}"
export COMPOSER_HOME
composer config -g audit.block-insecure false

echo "==> Package: ${PACKAGE_DIR}"
echo "==> Laravel constraint: ${LARAVEL_CONSTRAINT}"
echo "==> App directory: ${APP_DIR}"
echo "==> COMPOSER_HOME: ${COMPOSER_HOME}"

rm -rf "${APP_DIR}"

echo "==> Creating Laravel application (${LARAVEL_CONSTRAINT})"
composer create-project "laravel/laravel:${LARAVEL_CONSTRAINT}" "${APP_DIR}" \
  --prefer-dist \
  --no-interaction \
  --no-progress \
  --stability=stable

cd "${APP_DIR}"

echo "==> Configuring path repository for leefipay/mpesa"
composer config repositories.leefipay '{"type":"path","url":"'"${PACKAGE_DIR}"'","options":{"symlink":false}}'
composer config minimum-stability stable
composer config prefer-stable true

echo "==> Requiring local leefipay/mpesa"
composer require "leefipay/mpesa:@dev" --no-interaction --no-progress

echo "==> Writing fake CI credentials (no real secrets)"
{
  echo ""
  echo "LEEFIPAY_BASE_URL=${LEEFIPAY_BASE_URL:-http://localhost}"
  echo "LEEFIPAY_API_KEY=${LEEFIPAY_API_KEY:-test-key}"
  echo "LEEFIPAY_API_SECRET=${LEEFIPAY_API_SECRET:-test-secret}"
  echo "LEEFIPAY_ENVIRONMENT=${LEEFIPAY_ENVIRONMENT:-sandbox}"
  echo "LEEFIPAY_WEBHOOKS_ENABLED=${LEEFIPAY_WEBHOOKS_ENABLED:-true}"
  echo "LEEFIPAY_WEBHOOK_SECRET=${LEEFIPAY_WEBHOOK_SECRET:-whsec_test_secret}"
} >> .env

echo "==> Discovering packages"
php artisan package:discover --ansi

echo "==> Publishing leefipay-config"
php artisan vendor:publish --tag=leefipay-config --force --no-interaction

if [[ ! -f config/leefipay.php ]]; then
  echo "ERROR: config/leefipay.php was not published" >&2
  exit 1
fi
echo "OK: config/leefipay.php exists"

echo "==> Verifying service provider discovery"
php -r '
$packagesFile = "bootstrap/cache/packages.php";
if (! is_file($packagesFile)) {
    fwrite(STDERR, "ERROR: bootstrap/cache/packages.php missing after package:discover\n");
    exit(1);
}
$packages = require $packagesFile;
$found = false;
foreach ($packages as $name => $meta) {
    $providers = $meta["providers"] ?? [];
    foreach ($providers as $provider) {
        if ($provider === "LeefiPay\\Mpesa\\LeefiPayServiceProvider") {
            $found = true;
            echo "OK: discovered provider via {$name}\n";
            break 2;
        }
    }
}
if (! $found) {
    fwrite(STDERR, "ERROR: LeefiPayServiceProvider was not discovered\n");
    fwrite(STDERR, print_r($packages, true));
    exit(1);
}
'

echo "==> Resolving LeefiPayClient and Facade from the container"
php artisan tinker --execute="
\$client = app(\\LeefiPay\\Mpesa\\Services\\LeefiPayClient::class);
echo 'OK: client => ' . get_class(\$client) . PHP_EOL;
\$root = \\LeefiPay\\Mpesa\\Facades\\LeefiPay::getFacadeRoot();
echo 'OK: facade root => ' . get_class(\$root) . PHP_EOL;
if (! \$root instanceof \\LeefiPay\\Mpesa\\Services\\LeefiPayClient) {
    throw new RuntimeException('Facade root is not LeefiPayClient');
}
\$config = config('leefipay');
if (! is_array(\$config) || ! array_key_exists('base_url', \$config)) {
    throw new RuntimeException('leefipay config is not loaded');
}
echo 'OK: config base_url => ' . (\$config['base_url'] ?? '') . PHP_EOL;
"

echo "==> Laravel ${LARAVEL_CONSTRAINT} integration checks passed"
