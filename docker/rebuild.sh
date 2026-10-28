#!/bin/sh
set -eu

cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
    printf '%s\n' 'Missing .env file. Copy .env.example to .env and configure it before rebuilding.' >&2
    exit 1
fi

if ! command -v docker >/dev/null 2>&1; then
    printf '%s\n' 'Docker is not installed or is not on PATH. Install Docker Desktop for Mac first.' >&2
    exit 1
fi

if ! docker info >/dev/null; then
    printf '%s\n' 'Docker is not available. Start Docker Desktop and try again.' >&2
    exit 1
fi

get_env_value() {
    awk -v name="$1" -v fallback="$2" '
        index($0, name "=") == 1 {
            sub(/^[^=]*=/, "")
            sub(/^[[:space:]]+/, "")
            sub(/[[:space:]]+$/, "")
            print
            found = 1
            exit
        }
        END { if (!found) print fallback }
    ' .env
}

image_name=$(get_env_value DOCKER_IMAGE_NAME talent-referral-platform:latest)
container_name=$(get_env_value DOCKER_CONTAINER_NAME talent-referral-platform)
volume_name=$(get_env_value DOCKER_VOLUME_NAME talent-referral-platform-sqlite)
data_dir=$(get_env_value DOCKER_DATA_DIR '')
app_env=$(get_env_value APP_ENV production)
port=$(get_env_value APP_PORT 8000)
proxy_network=$(get_env_value DOCKER_PROXY_NETWORK '')
app_url=$(get_env_value APP_URL http://localhost)
app_url=$(node --input-type=module -e '
    const url = new URL(process.argv[1])
    if (!["http:", "https:"].includes(url.protocol)) throw new Error("APP_URL must use HTTP or HTTPS")
    url.port = process.argv[3] ? "" : process.argv[2]
    console.log(url.origin)
' "$app_url" "$port" "$proxy_network")

set -- -p "${port}:80"
case "$app_url" in
    https://*)
        if ! command -v mkcert >/dev/null 2>&1; then
            printf '%s\n' 'HTTPS requires mkcert. Run brew install mkcert, then mkcert -install.' >&2
            exit 1
        fi
        hostname=$(node -e 'console.log(new URL(process.argv[1]).hostname)' "$app_url")
        mkdir -p docker/certs
        mkcert -cert-file docker/certs/local.pem -key-file docker/certs/local-key.pem \
            "$hostname" localhost 127.0.0.1 ::1
        chmod 600 docker/certs/local-key.pem
        set -- -p "${port}:443" \
            -v "$(pwd)/docker/certs:/etc/apache2/local-certs:ro" \
            -v "$(pwd)/docker/local/web/https.conf:/etc/apache2/conf-enabled/local-https.conf:ro"
        ;;
esac

if [ -n "$proxy_network" ]; then
    if [ "${app_url#https://}" = "$app_url" ]; then
        printf '%s\n' 'DOCKER_PROXY_NETWORK requires an HTTPS APP_URL.' >&2
        exit 1
    fi
    docker network inspect "$proxy_network" >/dev/null
    set -- --network "$proxy_network" \
        -v "$(pwd)/docker/certs:/etc/apache2/local-certs:ro" \
        -v "$(pwd)/docker/local/web/https.conf:/etc/apache2/conf-enabled/local-https.conf:ro"
fi

printf 'Building image %s...\n' "$image_name"
docker build --build-arg "APP_ENV=${app_env}" -t "$image_name" .

existing_container=$(docker ps -aq -f "name=^${container_name}$")
if [ -n "$existing_container" ]; then
    printf '%s\n' 'Stopping and removing the existing container...'
    docker stop "$container_name" >/dev/null
    docker rm "$container_name" >/dev/null
fi

# Use a host folder for the SQLite data when DOCKER_DATA_DIR is set, so tools
# like DBeaver can open the database directly. Otherwise use a named volume.
if [ -n "$data_dir" ]; then
    printf 'Using database folder %s...\n' "$data_dir"
    mkdir -p "$data_dir"
    data_mount=$data_dir
else
    printf '%s\n' 'Ensuring database volume exists...'
    docker volume create "$volume_name" >/dev/null
    data_mount=$volume_name
fi

printf '%s\n' 'Starting container from new image...'
# Match the container clock to this machine unless .env sets TZ itself.
timezone=$(get_env_value TZ '')
if [ -z "$timezone" ]; then
    timezone=$(readlink /etc/localtime 2>/dev/null | sed -n 's|.*zoneinfo/||p')
fi
if [ -n "$timezone" ]; then
    printf 'Using timezone %s...\n' "$timezone"
    set -- "$@" -e "TZ=${timezone}"
fi

docker run -d --name "$container_name" "$@" \
    --env-file .env \
    -e "APP_URL=${app_url}" \
    -v "${data_mount}:/var/www/data" \
    -v "$(pwd)/public:/var/www/html/public" \
    "$image_name"

printf 'Done. App is available at %s\n' "$app_url"
