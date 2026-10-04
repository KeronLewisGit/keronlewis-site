#!/usr/bin/env bash
#
# Build a zip to extract straight into Hostinger's public_html, laid out the
# way Hostinger's Laravel guide describes: the whole project in public_html
# with a root .htaccess that sends every request into public/.
#
#   scripts/package-hostinger.sh            first install: includes .env and an empty database
#   scripts/package-hostinger.sh update     later releases: leaves the server's .env and database alone
#
# Optional: APP_URL=https://example.com OUT=/path/to.zip scripts/package-hostinger.sh
#
set -euo pipefail

MODE="${1:-install}"
[[ "$MODE" == "install" || "$MODE" == "update" ]] || { echo "usage: $0 [install|update]" >&2; exit 2; }

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
APP_URL="${APP_URL:-https://keronlewis.com}"
OUT="${OUT:-$HOME/Downloads/keronlewis-hostinger-$MODE.zip}"
STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT

cd "$ROOT"
echo "Building assets…"
npm run build --silent

echo "Copying the project…"
rsync -a \
    --exclude '.git' --exclude '.DS_Store' --exclude 'node_modules' --exclude 'vendor' \
    --exclude 'tests' --exclude 'scripts' --exclude 'phpunit.xml' --exclude '.phpunit.result.cache' \
    --exclude 'package.json' --exclude 'package-lock.json' --exclude 'vite.config.js' \
    --exclude 'resources/css' --exclude 'resources/js' \
    --exclude 'CLAUDE.md' --exclude 'AGENTS.md' --exclude 'README.md' --exclude '.npmrc' --exclude '.editorconfig' --exclude '.gitattributes' --exclude '.gitignore' \
    --exclude '/.env' --exclude '.env.example' --exclude '.deploy.env' --exclude '.deploy.env.example' --exclude 'database/*.sqlite*' \
    --exclude 'public/hot' --exclude 'bootstrap/cache/*.php' \
    --exclude 'storage/logs/*.log' --exclude 'storage/framework/views/*.php' \
    --exclude 'storage/framework/sessions/*' --exclude 'storage/framework/cache/data/*' \
    ./ "$STAGE/"

cd "$STAGE"
mkdir -p storage/logs storage/framework/{views,sessions,cache/data} bootstrap/cache

echo "Installing production dependencies…"
composer install --no-dev --optimize-autoloader --no-interaction --quiet

# The root .htaccess that sends every request into public/ is tracked in the repo and was copied above.
[[ -f .htaccess ]] || { echo "missing root .htaccess" >&2; exit 1; }

if [[ "$MODE" == "install" ]]; then
    HOST="$(echo "$APP_URL" | sed -E 's#^https?://##; s#/.*$##')"
    cat > .env <<ENV
APP_NAME="Keron Lewis"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=$APP_URL

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=sqlite

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync

# Contact-form email. While this says "log", messages are saved to the database
# but not emailed. To send real email, create a mailbox in hPanel, then set
# MAIL_MAILER=smtp and fill in the lines below.
MAIL_MAILER=log
# MAIL_HOST=smtp.hostinger.com
# MAIL_PORT=465
# MAIL_SCHEME=smtps
# MAIL_USERNAME=hello@$HOST
# MAIL_PASSWORD=
MAIL_FROM_ADDRESS="hello@$HOST"
MAIL_FROM_NAME="\${APP_NAME}"

# Where contact-form messages are emailed
CONTACT_TO=keronlewis@live.com
ENV
    php artisan key:generate --force --quiet
    touch database/database.sqlite
    php artisan migrate --force --quiet
fi

# Nothing compiled against this machine's paths may ship.
rm -f bootstrap/cache/config.php bootstrap/cache/routes-*.php storage/logs/*.log
find storage/framework/views -name '*.php' -delete

echo "Zipping…"
rm -f "$OUT"
zip -qr "$OUT" . -x '*.DS_Store'
echo "Done: $OUT ($(du -h "$OUT" | cut -f1 | tr -d ' '))"
