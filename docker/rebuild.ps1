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
$Port = Get-EnvValue 'APP_PORT' '8000'
$NetworkName = "$ContainerName-network"
$MailpitName = "$ContainerName-mailpit"
$MailpitPort = Get-EnvValue 'MAILPIT_PORT' '8025'

Write-Host "Building image $ImageName..."
docker build -t $ImageName .
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

Write-Host "Ensuring database volume exists..."
docker volume create $VolumeName | Out-Null

Write-Host "Ensuring application network and Mailpit are running..."
if (-not (docker network ls --filter "name=^$NetworkName$" --format '{{.Name}}')) {
    docker network create $NetworkName | Out-Null
    if ($LASTEXITCODE -ne 0) { throw "Docker network creation failed." }
}

$ExistingMailpit = docker ps -aq -f "name=^$MailpitName$"
if ($ExistingMailpit) {
    $MailpitNetworks = docker inspect --format '{{json .NetworkSettings.Networks}}' $MailpitName | ConvertFrom-Json
    if ($MailpitNetworks.PSObject.Properties.Name -notcontains $NetworkName) {
        docker network connect --alias mailpit $NetworkName $MailpitName
        if ($LASTEXITCODE -ne 0) { throw "Could not connect Mailpit to the application network." }
    }
    if (-not (docker ps -q -f "name=^$MailpitName$")) {
        docker start $MailpitName | Out-Null
        if ($LASTEXITCODE -ne 0) { throw "Could not start Mailpit." }
    }
} else {
    docker run -d --name $MailpitName --network $NetworkName --network-alias mailpit -p "${MailpitPort}:8025" axllent/mailpit:latest | Out-Null
    if ($LASTEXITCODE -ne 0) { throw "Could not start Mailpit." }
}

Write-Host "Starting container from new image..."
# Mount public/ so Laravel sees Vite's live hot file and dev fonts manifest.
$PublicPath = Join-Path $PWD 'public'
docker run -d --name $ContainerName --network $NetworkName -p "${Port}:80" --env-file .env -e "APP_URL=http://localhost:$Port" -v "${VolumeName}:/var/www/data" -v "${PublicPath}:/var/www/html/public" $ImageName
if ($LASTEXITCODE -ne 0) { throw "Could not start the application container." }

Write-Host "Done. App is available at http://localhost:$Port"
Write-Host "Mailpit inbox is available at http://localhost:$MailpitPort"
