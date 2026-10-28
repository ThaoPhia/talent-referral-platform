#!/bin/sh
set -e

DATA_DIR=/var/www/data

# Keep SQLite on the persistent mount unless .env chooses another database.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    export DB_DATABASE="${DB_DATABASE:-$DATA_DIR/database.sqlite}"

    db_dir=$(dirname "$DB_DATABASE")
    mkdir -p "$db_dir"
    if [ ! -f "$DB_DATABASE" ]; then
        touch "$DB_DATABASE"
    fi

    # SQLite writes journal files next to the database, so www-data needs the
    # folder as well as the file. Bind mounts may be owned by the host user.
    chown www-data:www-data "$db_dir" "$DB_DATABASE"
    chmod ug+rw "$DB_DATABASE"
    chmod ug+rwx "$db_dir"
fi

runuser -u www-data -- php artisan migrate --force

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
