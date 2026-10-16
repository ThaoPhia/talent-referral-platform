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
$AppUrl = Get-EnvValue 'APP_URL' 'http://localhost'
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
# Mount public/ so Laravel sees Vite's live hot file and dev fonts manifest.
$PublicPath = Join-Path $PWD 'public'
docker run -d --name $ContainerName -p "${Port}:80" --env-file .env -e "APP_URL=$AppUrl" -v "${VolumeName}:/var/www/data" -v "${PublicPath}:/var/www/html/public" $ImageName

Write-Host "Done. App is available at $AppUrl"
