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

# Uploaded resumes live on the persistent mount so they survive image rebuilds.
resume_dir="$DATA_DIR/resumes"
app_resume_dir=storage/app/private/resumes
mkdir -p "$resume_dir" "$(dirname "$app_resume_dir")"
if [ ! -L "$app_resume_dir" ]; then
    if [ -d "$app_resume_dir" ]; then
        cp -a "$app_resume_dir/." "$resume_dir/"
        rm -rf "$app_resume_dir"
    fi
    ln -s "$resume_dir" "$app_resume_dir"
fi
chown -R www-data:www-data "$resume_dir" "$(dirname "$app_resume_dir")"

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
