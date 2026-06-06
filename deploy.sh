#!/usr/bin/env bash

set -Eeuo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BRANCH="${DEPLOY_BRANCH:-main}"
REMOTE="${DEPLOY_REMOTE:-origin}"
LOCK_FILE="${DEPLOY_LOCK_FILE:-/tmp/krakatau-crm-deploy.lock}"

cd "$APP_DIR"

if [[ ! -f artisan ]]; then
    echo "Error: deploy.sh must be run from the Laravel project root."
    exit 1
fi

for command in git composer npm php; do
    if ! command -v "$command" >/dev/null 2>&1; then
        echo "Error: required command not found: $command"
        exit 1
    fi
done

(
    flock -n 9 || {
        echo "Error: another deployment is already running."
        exit 1
    }

    echo "Deploying Krakatau CRM from ${REMOTE}/${BRANCH}"

    git fetch "$REMOTE" "$BRANCH"
    git pull --ff-only "$REMOTE" "$BRANCH"

    composer install --no-dev --optimize-autoloader

    npm ci
    npm run build

    php artisan migrate --force

    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    echo "Deployment completed successfully."
) 9>"$LOCK_FILE"
