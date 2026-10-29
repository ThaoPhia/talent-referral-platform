$ErrorActionPreference = 'Stop'

function Get-EnvValue($name, $default) {
    $line = Get-Content .env | Where-Object { $_ -match "^$name=" } | Select-Object -First 1
    if ($line) {
        return ($line -split '=', 2)[1].Trim()
    }
    return $default
}

$ImageName = Get-EnvValue 'DOCKER_IMAGE_NAME' 'talent-referral-platform:latest'
$ContainerName = Get-EnvValue 'DOCKER_CONTAINER_NAME' 'talent-referral-platform'
$VolumeName = Get-EnvValue 'DOCKER_VOLUME_NAME' 'talent-referral-platform-sqlite'
$DataDir = Get-EnvValue 'DOCKER_DATA_DIR' ''
$AppEnv = Get-EnvValue 'APP_ENV' 'production'
$Port = Get-EnvValue 'APP_PORT' '8000'
$AppUrl = Get-EnvValue 'APP_URL' 'http://localhost'
$MailHost = Get-EnvValue 'MAIL_HOST' ''
$SharedMailpit = Get-EnvValue 'DOCKER_SHARED_MAILPIT' ''
$MailpitPort = Get-EnvValue 'DOCKER_MAILPIT_PORT' '8026'
$MailpitImage = Get-EnvValue 'DOCKER_MAILPIT_IMAGE' 'axllent/mailpit:latest'
try {
    $AppUri = [UriBuilder]::new($AppUrl)
    if ($AppUri.Scheme -notin @('http', 'https')) {
        throw 'APP_URL must use HTTP or HTTPS'
    }
    $AppUri.Port = [int]$Port
    $AppUrl = $AppUri.Uri.GetLeftPart([UriPartial]::Authority)
} catch {
    throw "Invalid APP_URL or APP_PORT: $($_.Exception.Message)"
}
if ($AppUri.Scheme -eq 'https') {
    throw 'HTTPS Docker rebuilds are supported by docker/rebuild.sh on macOS/Linux only.'
}

Write-Host "Building image $ImageName..."
docker build --build-arg "APP_ENV=$AppEnv" -t $ImageName .
if ($LASTEXITCODE -ne 0) {
    throw "Docker image build failed."
}

Write-Host "Stopping existing container (if running)..."
$ExistingContainer = docker ps -aq -f "name=^$ContainerName$"
if ($ExistingContainer) {
    docker stop $ContainerName | Out-Null

    Write-Host "Removing existing container (if present)..."
    docker rm $ContainerName | Out-Null
}

# Use a host folder for the SQLite data when DOCKER_DATA_DIR is set, so tools
# like DBeaver can open the database directly. Otherwise use a named volume.
if ($DataDir) {
    Write-Host "Using database folder $DataDir..."
    New-Item -ItemType Directory -Force -Path $DataDir | Out-Null
    $DataMount = $DataDir
} else {
    Write-Host "Ensuring database volume exists..."
    docker volume create $VolumeName | Out-Null
    $DataMount = $VolumeName
}

# When .env sends mail to "mailpit", use the shared Mailpit container named by
# DOCKER_SHARED_MAILPIT (starting it if it's stopped), or run a Mailpit inbox
# for this project on a private network that only the app joins.
$MailpitContainer = $null
$MailNetwork = $null
$MailEnv = @()
if ($MailHost -eq 'mailpit' -and $SharedMailpit) {
    docker container inspect $SharedMailpit *> $null
    if ($LASTEXITCODE -eq 0) {
        if ((docker container inspect -f '{{.State.Running}}' $SharedMailpit) -ne 'true') {
            Write-Host "Starting shared Mailpit $SharedMailpit..."
            docker start $SharedMailpit | Out-Null
        }
        $MailNetwork = docker container inspect -f '{{range $name, $_ := .NetworkSettings.Networks}}{{println $name}}{{end}}' $SharedMailpit |
            Where-Object { $_ -and $_ -notin @('bridge', 'host', 'none') } |
            Select-Object -First 1
    }

    if ($MailNetwork) {
        Write-Host "Using shared Mailpit $SharedMailpit..."
        # Address it by container name, so it doesn't need a "mailpit" alias.
        $MailEnv = @('-e', "MAIL_HOST=$SharedMailpit")
        if (docker ps -aq -f "name=^$ContainerName-mailpit$") {
            docker rm -f "$ContainerName-mailpit" | Out-Null
        }
    } else {
        Write-Warning "Shared Mailpit $SharedMailpit was not found on a Docker network; starting this project's own Mailpit instead."
    }
}

if ($MailHost -eq 'mailpit' -and -not $MailNetwork) {
    $MailpitContainer = "$ContainerName-mailpit"
    $MailNetwork = "$ContainerName-mail"

    docker network inspect $MailNetwork *> $null
    if ($LASTEXITCODE -ne 0) {
        docker network create $MailNetwork | Out-Null
    }

    if (docker ps -aq -f "name=^$MailpitContainer$") {
        docker rm -f $MailpitContainer | Out-Null
    }

    Write-Host "Starting Mailpit inbox on http://127.0.0.1:$MailpitPort..."
    docker volume create $MailpitContainer | Out-Null
    docker run -d --name $MailpitContainer --restart unless-stopped --network $MailNetwork --network-alias mailpit -p "127.0.0.1:${MailpitPort}:8025" -e MP_DATABASE=/data/mailpit.db -v "${MailpitContainer}:/data" $MailpitImage | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "Failed to start Mailpit."
    }
}

Write-Host "Starting container from new image..."
# Mount public/ so Laravel sees Vite's live hot file and dev fonts manifest.
$PublicPath = Join-Path $PWD 'public'
docker run -d --name $ContainerName -p "${Port}:80" --env-file .env @MailEnv -e "APP_URL=$AppUrl" -v "${DataMount}:/var/www/data" -v "${PublicPath}:/var/www/html/public" $ImageName

if ($MailHost -eq 'mailpit') {
    docker network connect $MailNetwork $ContainerName
}

Write-Host "Done. App is available at $AppUrl"
if ($MailpitContainer) {
    Write-Host "Mail inbox is available at http://127.0.0.1:$MailpitPort"
} elseif ($MailHost -eq 'mailpit') {
    $MailpitUrl = docker port $SharedMailpit 8025/tcp 2>$null | Select-Object -First 1
    Write-Host "Mail is sent to the shared Mailpit $SharedMailpit$(if ($MailpitUrl) { " (inbox: http://$MailpitUrl)" })"
}
