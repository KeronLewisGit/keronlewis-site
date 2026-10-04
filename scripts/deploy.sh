#!/usr/bin/env bash
#
# Publish the site in one step: build the assets, run the tests, commit and
# push, then update the server over SSH.
#
#   scripts/deploy.sh "what changed"     commit everything with that message, then deploy
#   scripts/deploy.sh                    deploy what is already committed
#
# The server's address is read from .deploy.env; see .deploy.env.example.
#
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

[[ -f .deploy.env ]] || { echo "Missing .deploy.env. Copy .deploy.env.example to .deploy.env and fill it in." >&2; exit 1; }
# shellcheck disable=SC1091
source .deploy.env
: "${DEPLOY_SSH:?Set DEPLOY_SSH in .deploy.env}"
DEPLOY_PORT="${DEPLOY_PORT:-22}"
DEPLOY_PATH="${DEPLOY_PATH:-domains/keronlewis.dev/public_html}"
MESSAGE="${1:-}"

echo "Building assets…"
npm run build --silent

echo "Running tests…"
php artisan test --compact

if [[ -n "$(git status --porcelain)" ]]; then
    [[ -n "$MESSAGE" ]] || { echo "There are uncommitted changes. Run: scripts/deploy.sh \"what changed\"" >&2; exit 1; }
    git add -A
    git commit -q -m "$MESSAGE"
    echo "Committed: $MESSAGE"
fi

echo "Pushing to GitHub…"
git push -q origin HEAD

echo "Updating the server…"
ssh -p "$DEPLOY_PORT" "$DEPLOY_SSH" "bash -se" <<REMOTE
set -euo pipefail
cd "$DEPLOY_PATH"
git pull -q --ff-only
composer2 install --no-dev --optimize-autoloader --no-interaction --quiet
php artisan migrate --force
php artisan optimize:clear --quiet
echo "Server is now at: \$(git log --oneline -1)"
REMOTE

echo "Done."
