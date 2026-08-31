#!/bin/bash
#
# Deploy the current branch to this server.
#
# Wraps the steps that have to happen together and in order: without the cache
# clears, Blade and route changes do not take effect, which looks exactly like
# the deploy having silently failed.
#
#   sudo bash scripts/deploy.sh                    # deploy the current branch
#   sudo bash scripts/deploy.sh --migrate          # and run new migrations
#   sudo bash scripts/deploy.sh --branch <name>    # deploy a specific branch
#
set -euo pipefail

APP_DIR="/var/www/moka"
FPM="php8.2-fpm"

BRANCH=""
MIGRATE=0
while [ $# -gt 0 ]; do
    case "$1" in
        --migrate)      MIGRATE=1; shift ;;
        --branch)       BRANCH="${2:-}"; shift 2 ;;
        -h|--help)      sed -n '2,12p' "$0"; exit 0 ;;
        *)              echo "unknown option: $1" >&2; exit 1 ;;
    esac
done

cd "$APP_DIR"
[ -n "$BRANCH" ] || BRANCH="$(git rev-parse --abbrev-ref HEAD)"

echo "==> deploying $BRANCH"

# Uncommitted work here is almost always an accident — someone editing on the
# server. Stop rather than have the pull bury it.
if ! git diff --quiet || ! git diff --cached --quiet; then
    echo "ERROR: uncommitted changes in $APP_DIR — commit or discard them first" >&2
    git status --short >&2
    exit 1
fi

BEFORE="$(git rev-parse HEAD)"
git pull origin "$BRANCH"
AFTER="$(git rev-parse HEAD)"

if [ "$BEFORE" = "$AFTER" ]; then
    echo "==> already up to date"
else
    echo "==> $(git rev-list --count "$BEFORE..$AFTER") new commit(s)"
    git --no-pager log --oneline "$BEFORE..$AFTER" | sed 's/^/    /'
fi

# Only when composer.lock actually moved; installing every time is slow and can
# swap out working dependencies for no reason.
if ! git diff --quiet "$BEFORE" "$AFTER" -- composer.lock 2>/dev/null; then
    echo "==> composer.lock changed, installing"
    composer install --no-dev --optimize-autoloader --no-interaction
fi

if [ "$MIGRATE" = "1" ]; then
    echo "==> migrations"
    php artisan migrate --force
else
    # Silence is the dangerous case: a deploy that needed migrations and did not
    # get them fails later, in the browser, as a missing-column error.
    PENDING="$(php artisan migrate:status 2>/dev/null | grep -c 'Pending' || true)"
    if [ "${PENDING:-0}" -gt 0 ]; then
        echo "==> NOTE: $PENDING pending migration(s); re-run with --migrate to apply"
    fi
fi

echo "==> clearing caches"
php artisan view:clear
php artisan route:clear
php artisan config:clear

echo "==> reloading $FPM"
systemctl reload "$FPM"

echo "==> done: $(git rev-parse --short HEAD) on $BRANCH"
