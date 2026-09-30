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

Write-Host "Starting container from new image..."
docker run -d --name $ContainerName -p "${Port}:80" --env-file .env -e "APP_URL=http://localhost:$Port" -v "${VolumeName}:/var/www/data" $ImageName

Write-Host "Done. App is available at http://localhost:$Port"
