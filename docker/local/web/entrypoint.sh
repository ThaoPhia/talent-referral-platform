#!/bin/sh
set -e

if [ ! -f /var/www/data/database.sqlite ]; then
    touch /var/www/data/database.sqlite
    chown www-data:www-data /var/www/data/database.sqlite
fi

php artisan migrate --force

# Run Laravel's scheduler alongside Apache, restarting it if it ever exits.
if [ "$1" = "apache2-foreground" ] && [ "${RUN_SCHEDULER:-true}" = "true" ]; then
    (
        while true; do
            runuser -u www-data -- php artisan schedule:work || true
            echo 'Laravel scheduler stopped; restarting in 5 seconds...' >&2
            sleep 5
        done
    ) &
fi

exec "$@"
