#!/bin/sh
set -e

mkdir -p /data
touch /data/database.sqlite

# Make an app key on first run and keep it in the data volume, so there's
# nothing to set up by hand.
if [ -z "$APP_KEY" ]; then
    [ -s /data/app.key ] || php -r 'echo "base64:".base64_encode(random_bytes(32));' > /data/app.key
    APP_KEY="$(cat /data/app.key)"
    export APP_KEY
fi

if [ "$1" = "serve" ]; then
    php artisan migrate --force
    php artisan optimize
    exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload
fi

exec php artisan "$@"
