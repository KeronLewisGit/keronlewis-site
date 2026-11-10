---
title: How to Deploy Laravel on Shared Hosting (Even Without SSH)
seo_title: Deploy Laravel on Shared Hosting Without SSH (Step by Step)
description: Deploy Laravel 13 to shared hosting with no SSH: build a zip locally, keep the app out of the web root, run artisan via cron, swap releases safely.
category: Laravel & PHP
keyword: deploy laravel shared hosting
date: 2026-11-10
---

To deploy Laravel on shared hosting without SSH, build everything on your own machine (Composer dependencies and front-end assets included), upload one zip through the hosting file manager, keep the Laravel app outside the public web folder with only `index.php` and assets exposed, and run artisan commands such as migrations through one-off cron jobs that call the right PHP binary.

For updates, unzip each new release beside the live one, copy over `.env` and `storage`, migrate, then swap folders and keep the previous release so you can roll back in seconds. It isn't as slick as a CI pipeline to a VPS, but it is reliable and costs a few dollars a month.

This is the process I use for Laravel 13 sites on shared hosting, including my own company site, [Code Canvas](/work/code-canvas), which runs this way. Hostinger is the host in my examples, but the approach works on most cPanel-style and custom-panel hosts.

## What shared hosting changes about a Laravel deploy

Laravel's [deployment docs](https://laravel.com/docs/13.x/deployment) assume you control the server. On shared hosting you usually don't, and on some plans you don't get SSH at all. Here is what changes:

| Task | On a VPS | On shared hosting without SSH |
| --- | --- | --- |
| Install dependencies | `composer install` on the server | Run it locally, ship `vendor/` in the zip |
| Build assets | `npm run build` in CI | Run it locally, ship `public/build` |
| Web root | Point Nginx at `public/` | Keep `public_html`, move the app beside it |
| Run migrations | `php artisan migrate` over SSH | A cron job runs it once |
| Zero-downtime deploys | Symlinked releases | Folder swap with the old release kept |
| Scheduler and queues | Supervisor plus cron | One cron entry, queue drained by the scheduler |
| Mail | Any driver | SMTP only if `proc_open` is disabled |

Before you start, check the plan supports what you need: PHP 8.3 or newer (Laravel 13's minimum), MySQL or MariaDB, cron jobs, and the `pdo_mysql`, `mbstring`, `openssl`, `curl` and `dom` extensions.

## Step 1: Match PHP versions before you build anything

This is the step that caught me. Three PHP versions are in play, and they don't have to match unless you make them:

1. **Your local PHP**, which Composer uses to choose package versions.
2. **The site's web PHP**, picked in the hosting panel.
3. **The server's command-line PHP**, which cron uses. On many shared hosts the default `php` command is older than the version you picked for the website.

If you run `composer update` locally on PHP 8.4, Composer can install packages that need 8.4. Symfony 8 components, which Laravel can install, require PHP 8.4, and on an 8.3 server they fail with a parse error before Laravel even boots. Either switch the site to PHP 8.4, or tell Composer the lowest version you'll deploy to in `composer.json`:

```json
"config": {
    "platform": {
        "php": "8.3.0"
    }
}
```

Composer then resolves dependencies as if it were running on that version. The setting is documented under [platform in Composer's config docs](https://getcomposer.org/doc/06-config.md#platform).

For the command-line PHP, hosts running CloudLinux usually keep each version at a path like `/opt/alt/php84/usr/bin/php`. Others use `/usr/local/bin/php` or `/usr/bin/php`. Without SSH, find out with a one-off cron job:

```bash
ls -d /opt/alt/php*/usr/bin/php > "$HOME/php-check.txt" 2>&1; php -v >> "$HOME/php-check.txt" 2>&1
```

Open `php-check.txt` in the file manager, then use the full path to the right binary in every cron command from here on.

## Step 2: Build a release zip on your machine

Everything the server would normally build gets built locally. A short script keeps it repeatable:

```bash
#!/usr/bin/env bash
# scripts/package.sh: builds dist/release.zip with app/ and public/ folders
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
STAGE="$ROOT/dist/stage"
ZIP="$ROOT/dist/release.zip"
cd "$ROOT"

npm ci
npm run build

rm -rf "$STAGE" "$ZIP"
mkdir -p "$STAGE/app" "$STAGE/public"

# Only committed files ship: no local .env, logs or editor clutter
git ls-files -z -- . ':!:tests' ':!:public' \
  | rsync -a --from0 --files-from=- "$ROOT/" "$STAGE/app/"

(cd "$STAGE/app" && composer install --no-dev --optimize-autoloader --no-interaction)

mkdir -p "$STAGE/app/storage/framework/"{cache/data,sessions,views} \
         "$STAGE/app/storage/logs" "$STAGE/app/storage/app/public" "$STAGE/app/bootstrap/cache"

cp -R public/build public/favicon.ico public/robots.txt "$STAGE/public/"
cp deploy/index.php deploy/.htaccess "$STAGE/public/"

(cd "$STAGE" && zip -qr "$ZIP" . -x '*.DS_Store')
echo "Built $ZIP"
```

Shipping only `git ls-files` output matters. It keeps your local `.env`, SQLite files and anything else you haven't committed off the server. The zip has two folders: `app/`, the Laravel application with `vendor/`, and `public/`, the files that will be web-accessible.

## Step 3: Keep the app out of the web root

The web server must never serve your `.env`, logs or `vendor` folder. The cleanest layout on shared hosting puts the app beside `public_html`, not inside it:

```text
domains/example.com/
├── public_html/      index.php, .htaccess, build/, favicon, robots.txt
├── myapp/            the Laravel app: vendor/, .env, storage/
└── deploy/           release zips, deploy scripts and logs
```

Replace the stock `public/index.php` with a front controller that points at the app folder and tells Laravel where its public files are:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$appPath = dirname(__DIR__).'/myapp';

if (file_exists($maintenance = $appPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appPath.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appPath.'/bootstrap/app.php';

$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
```

The `.htaccess` is Laravel's standard one, plus an HTTPS redirect if your host doesn't force it.

If your host only lets you write inside `public_html`, put the app in a subfolder there and block it twice: an `.htaccess` inside it with `Require all denied`, and a rule in the root `.htaccess` such as `RewriteRule ^myapp(/|$) - [F,L]`. Then request `/myapp/.env` in a browser and confirm you get a 403. Don't assume dotfiles are hidden either: on one host I deploy to, files beginning with `.ht` were served like any other file, so I keep backups and secrets out of the web root entirely.

## Step 4: Run artisan without SSH using cron

Without a shell, cron is your command line. Most hosting panels let you add a cron job with a custom command. On Hostinger it's under the site's Cron Jobs page, and schedules run in UTC, per [Hostinger's cron guide](https://www.hostinger.com/support/1583465-how-to-set-up-a-cron-job-at-hostinger/).

Rather than adding and deleting cron entries for each command, I use a script that runs every minute and does nothing unless a flag file exists:

```bash
#!/usr/bin/env bash
# deploy/run-once.sh: runs the commands below once each time run-once.flag is uploaded
set -euo pipefail
PHP=/opt/alt/php84/usr/bin/php
APP="$HOME/domains/example.com/myapp"
FLAG="$HOME/domains/example.com/deploy/run-once.flag"

[ -f "$FLAG" ] || exit 0
rm -f "$FLAG"

{
  echo "== $(date -u '+%F %T') UTC"
  "$PHP" "$APP/artisan" migrate --force
  "$PHP" "$APP/artisan" optimize
} >> "$HOME/domains/example.com/deploy/run-once.log" 2>&1
```

The cron command is `/bin/bash /home/USERNAME/domains/example.com/deploy/run-once.sh`, every minute. To run it, upload an empty `run-once.flag` with the file manager, wait a minute and read the log. Edit the command list for one-off jobs such as `key:generate --force` on first install or `db:seed --force`.

For the very first install you can also use a token-protected `setup.php` in the web root that boots Laravel, runs the setup commands and deletes itself. It's handy for handing a site to a client who has no SSH, but it's a public script, so it needs a long random token compared with `hash_equals()` and it must remove itself when done. Cron is safer when you can use it.

## Step 5: Ship updates with a release swap you can roll back

Extracting a new zip over the live site works until the day it doesn't: deleted files stay behind, a half-extracted upload serves broken pages, and there's no quick way back. Instead, this script deploys whatever zip lands in `deploy/release.zip`:

```bash
#!/usr/bin/env bash
# deploy/release.sh: cron runs this every minute. It deploys deploy/release.zip when one appears.
set -euo pipefail

PHP=/opt/alt/php84/usr/bin/php          # the host's PHP 8.4 CLI, not the default "php"
SITE="$HOME/domains/example.com"
WEB="$SITE/public_html"
APP="$SITE/myapp"                       # the live Laravel app, outside the web root
DEPLOY="$SITE/deploy"                   # upload release.zip here
ZIP="$DEPLOY/release.zip"
STAMP="$(date -u +%Y%m%d-%H%M%S)"
NEW="$SITE/release-$STAMP"
PREV="$SITE/release-prev-$STAMP"

[ -f "$ZIP" ] || exit 0                             # nothing to deploy
mkdir "$DEPLOY/release.lock" 2>/dev/null || exit 0  # a previous run is still busy
trap 'rmdir "$DEPLOY/release.lock"' EXIT
exec >>"$DEPLOY/release.log" 2>&1
unzip -tq "$ZIP" >/dev/null || { echo "$STAMP zip incomplete, retrying next minute"; exit 0; }

echo "== $STAMP starting"
trap 'echo "FAILED on line $LINENO"; mv "$ZIP" "$DEPLOY/failed-$STAMP.zip"; "$PHP" "$APP/artisan" up' ERR

# 1. Unpack the new release beside the live one
mkdir "$NEW"
unzip -q "$ZIP" -d "$NEW"

# 2. Pause the site, then copy over what the zip doesn't contain
"$PHP" "$APP/artisan" down --retry=30
cp "$APP/.env" "$NEW/app/.env"
rm -rf "$NEW/app/storage"
cp -a "$APP/storage" "$NEW/app/storage"

# 3. Migrate using the new code
"$PHP" "$NEW/app/artisan" migrate --force

# 4. Swap, keeping the old app and its built assets for rollback
mv "$APP" "$PREV"
mv "$NEW/app" "$APP"
if [ -d "$WEB/build" ]; then mv "$WEB/build" "$PREV/public-build"; fi
mv "$NEW/public/build" "$WEB/build"
cp -R "$NEW/public/." "$WEB/"
rm -rf "$NEW"

# 5. Build caches after the move: they store absolute paths
"$PHP" "$APP/artisan" optimize
"$PHP" "$APP/artisan" up

mv "$ZIP" "$DEPLOY/deployed-$STAMP.zip"
ls -d "$SITE"/release-prev-* | sort -r | tail -n +2 | xargs rm -rf   # keep one previous release
echo "== $STAMP live"
```

A few details that took me a while to get right:

- **The zip test.** Cron may fire while a large upload is still in progress. `unzip -tq` fails on a partial file, so the script waits for the next minute.
- **Maintenance mode before copying storage.** `artisan down` stops new writes (sessions, uploads, logs), so the copy is consistent. The down flag lives in `storage`, so the new release starts in maintenance mode too, and `up` on the new app brings it back.
- **Caches after the swap.** `config:cache` and `view:cache` write absolute paths. Cache inside `release-...` and then rename the folder, and Laravel looks for files in a folder that no longer exists.
- **Failures don't loop.** If migration fails, the zip is renamed to `failed-...zip` so cron doesn't retry it every minute, and the old release comes back up.
- **Keep migrations additive.** The old code runs for a moment against the new schema, and a rollback runs it there indefinitely. Add columns and tables freely; rename or drop them in a later release.

Rolling back is the swap in reverse:

```bash
#!/usr/bin/env bash
# deploy/rollback.sh: run once to put the previous release back.
set -euo pipefail

PHP=/opt/alt/php84/usr/bin/php
SITE="$HOME/domains/example.com"
WEB="$SITE/public_html"
APP="$SITE/myapp"
STAMP="$(date -u +%Y%m%d-%H%M%S)"
PREV="$(ls -d "$SITE"/release-prev-* | sort -r | head -n 1)"
FAILED="$SITE/release-failed-$STAMP"

mv "$APP" "$FAILED"
mv "$WEB/build" "$FAILED/public-build"
mv "$PREV" "$APP"
mv "$APP/public-build" "$WEB/build"

"$PHP" "$APP/artisan" optimize
"$PHP" "$APP/artisan" up
```

Migrations aren't reversed, and files uploaded since the deploy sit in the failed release's `storage`, so copy those back if it matters. The script assumes a live app already exists; do the first install by extracting the zip by hand and running setup through the run-once script.

## Step 6: Scheduler, queues, mail and storage links

**Scheduler.** One cron entry, every minute, with the full PHP path:

```bash
/opt/alt/php84/usr/bin/php /home/USERNAME/domains/example.com/myapp/artisan schedule:run >> /dev/null 2>&1
```

Remember the host's cron clock may be UTC while your app runs on `America/Port_of_Spain` or another timezone. Laravel's scheduler uses the app timezone (or a per-task `timezone()`), so check the times in the [scheduling docs](https://laravel.com/docs/13.x/scheduling) rather than doing the arithmetic in the panel.

**Queues.** There's no Supervisor to keep `queue:work` alive. For low volumes, `QUEUE_CONNECTION=sync` is fine. Otherwise use the database driver and let the scheduler drain it:

```php
// routes/console.php
use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:work --stop-when-empty --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();
```

**Mail.** Many shared hosts disable `proc_open`, which the `sendmail` transport needs. Use SMTP with the host's mailbox or a transactional email service, and test it before launch.

**Storage link.** `php artisan storage:link` creates the link under the app's own `public/` folder, because the command line never runs your custom `index.php` and doesn't know about `usePublicPath`. Create it yourself with a run-once command, `ln -s "$APP/storage/app/public" "$WEB/storage"`, or skip public disk links and serve files through a controller. Some hosts don't allow symlinks; test it.

## After your first deploy

Run through this before you call it done:

- [ ] `/.env`, `/myapp/.env` and `/storage/logs/laravel.log` return 403 or 404
- [ ] `APP_DEBUG=false` and `APP_ENV=production` (`php artisan about` in a run-once job confirms it)
- [ ] The scheduler cron is in place and a test task writes to the log
- [ ] A test email arrives
- [ ] A release swap and a rollback both work, tested before you need them
- [ ] Database and `storage` backups run off the server

The [Laravel security checklist](/blog/laravel-security-checklist) covers the rest of a pre-launch review. If you're still deciding whether a framework app is worth this effort compared with a CMS, [Laravel vs WordPress](/blog/laravel-vs-wordpress) compares the two. And if you'd rather have someone set up a deploy like this for your business, that's part of my [website development](/services/website-development) work.

## Frequently asked questions

### Can you run Laravel on shared hosting?

Yes. Laravel runs on any host with a supported PHP version (8.3 or newer for Laravel 13), the required extensions and a MySQL or MariaDB database. The limits are around long-running processes: no Supervisor for queue workers and no WebSockets server. Most business sites, booking systems and admin panels run fine; heavy background processing is better on a VPS.

### How do I run php artisan commands without SSH?

Use your hosting panel's cron jobs. Point a cron entry at a small shell script that runs the artisan commands you need with the full path to the right PHP binary and writes the output to a log file. Make the script exit early unless a flag file exists, so you can trigger it by uploading that file.

### Why does my Laravel site work but cron jobs fail?

Usually the command-line PHP is a different version from the one serving the website. The panel's PHP setting often only changes the web version, while `php` in cron points to the server default. Use the full path to the matching binary, such as `/opt/alt/php84/usr/bin/php` on CloudLinux hosts, and check the cron output or log for parse errors.

### Should I upload the vendor folder to shared hosting?

Yes, if you can't run Composer on the server. Run `composer install --no-dev --optimize-autoloader` locally, using Composer's platform setting to match the server's PHP version, and include `vendor/` in your release zip. That also makes deploys repeatable, because the exact package versions you tested are the ones that go live.

### How do I roll back a Laravel deploy on shared hosting?

Keep the previous release folder when you deploy. Instead of extracting over the live app, unzip each release beside it, swap folder names, and move the old one aside. Rolling back is the reverse rename plus `php artisan optimize`. Database migrations aren't undone automatically, so keep them additive and drop or rename columns in a later release.
