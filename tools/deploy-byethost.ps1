param(
  [string]$Config = "$PSScriptRoot\..\.byethost.local.json",
  [switch]$DryRun
)

$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path "$PSScriptRoot\..").Path
$curl = 'C:\Windows\System32\curl.exe'
if (-not (Test-Path $curl)) { $curl = 'curl.exe' }

if (-not (Test-Path $Config)) {
  Write-Host "Falta el archivo de configuracion: $Config" -ForegroundColor Yellow
  Write-Host 'Copia tools\byethost.config.example.json a .byethost.local.json y rellena los datos.' -ForegroundColor Yellow
  exit 1
}

$cfg = Get-Content $Config -Raw | ConvertFrom-Json
foreach ($k in @('siteUrl','ftpHost','ftpUser','ftpPass','dbHost','dbName','dbUser','dbPass')) {
  if (-not $cfg.$k) { throw "Falta el campo '$k' en $Config" }
}
$remoteRoot = if ($cfg.remoteRoot) { $cfg.remoteRoot.TrimEnd('/') } else { '/htdocs' }

# 1) robots.txt y sitemap.xml con el dominio real
& "$PSScriptRoot\set-domain.ps1" -BaseUrl $cfg.siteUrl -Root $projectRoot | Out-Null
Write-Host "Dominio SEO: $($cfg.siteUrl)" -ForegroundColor Cyan

# 2) .env de produccion (se sube al final, nunca se guarda en el repo)
$envLines = @(
  "DB_HOST=$($cfg.dbHost)"
  "DB_NAME=$($cfg.dbName)"
  "DB_USER=$($cfg.dbUser)"
  "DB_PASS=$($cfg.dbPass)"
  'DB_CHARSET=utf8mb4'
  'DB_PORT=3306'
  ''
  'APP_NAME=NovaTeam'
  'APP_ENV=production'
  'SESSION_NAME=NOVATEAMSESS'
  ''
  "BREVO_API_KEY=$($cfg.brevoApiKey)"
  "SMTP_FROM=$($cfg.smtpFrom)"
  'SMTP_FROM_NAME=NovaTeam'
  "CONTACTO_EMAIL=$($cfg.smtpFrom)"
)
$envTmp = Join-Path $env:TEMP 'novateam-prod.env'
[System.IO.File]::WriteAllText($envTmp, ($envLines -join "`r`n") + "`r`n", (New-Object System.Text.UTF8Encoding($false)))

# 3) Lista de archivos a subir
$excludePathParts = @('\.git\', '\.well-known\', '\uploads\archivos\2026\', '\uploads\perfiles\2026\', '\node_modules\', '\tools\')
$excludeRel = @('.byethost.local.json', '.env', '.env.example', '_dev_login.php', 'Dockerfile', 'entrypoint.sh', 'DEPLOY_BYETHOST.md')
$files = Get-ChildItem -Path $projectRoot -Recurse -File | Where-Object {
  $rel = $_.FullName.Substring($projectRoot.Length).TrimStart('\')
  if ($excludeRel -contains $rel) { return $false }
  foreach ($p in $excludePathParts) { if ($_.FullName -like "*$p*") { return $false } }
  return $true
}

Write-Host "Archivos a subir: $($files.Count)"
if ($DryRun) {
  $files | ForEach-Object { $_.FullName.Substring($projectRoot.Length) } | Sort-Object
  Write-Host "(simulacion: no se subio nada)" -ForegroundColor Yellow
  exit 0
}

# 4) Prueba de conexion FTP
$list = & $curl -sS --connect-timeout 25 --max-time 60 --user "$($cfg.ftpUser):$($cfg.ftpPass)" "ftp://$($cfg.ftpHost)$remoteRoot/" 2>&1
if ($LASTEXITCODE -ne 0) {
  Write-Host "No se pudo conectar por FTP: $list" -ForegroundColor Red
  exit 1
}
Write-Host "Conexion FTP OK ($remoteRoot)" -ForegroundColor Green

# 5) Subida con reintentos
$failed = @()
$i = 0
foreach ($f in $files) {
  $i++
  $rel = $f.FullName.Substring($projectRoot.Length).TrimStart('\') -replace '\\', '/'
  $url = "ftp://$($cfg.ftpHost)$remoteRoot/$rel"
  $ok = $false
  for ($try = 1; $try -le 3 -and -not $ok; $try++) {
    $out = & $curl -sS --ftp-create-dirs --connect-timeout 25 --max-time 180 --user "$($cfg.ftpUser):$($cfg.ftpPass)" -T $f.FullName $url 2>&1
    if ($LASTEXITCODE -eq 0) { $ok = $true }
  }
  if (-not $ok) {
    $failed += "$rel -> $out"
    Write-Host "[FALLO] $rel" -ForegroundColor Red
  } elseif ($i % 20 -eq 0 -or $i -eq $files.Count) {
    Write-Host "[$i/$($files.Count)] subidos" -ForegroundColor DarkGray
  }
}

# 6) .env
$envOut = & $curl -sS --connect-timeout 25 --max-time 60 --user "$($cfg.ftpUser):$($cfg.ftpPass)" -T $envTmp "ftp://$($cfg.ftpHost)$remoteRoot/.env" 2>&1
if ($LASTEXITCODE -ne 0) { $failed += ".env -> $envOut" } else { Write-Host '.env subido' -ForegroundColor Green }

Remove-Item $envTmp -Force -ErrorAction SilentlyContinue

if ($failed.Count -gt 0) {
  Write-Host "`nFallaron $($failed.Count) archivos:" -ForegroundColor Yellow
  $failed | ForEach-Object { Write-Host "  $_" -ForegroundColor Red }
  exit 1
}

Write-Host "`nSubida completa." -ForegroundColor Green
Write-Host "Abre $($cfg.siteUrl) y verifica landing, login y robots.txt." -ForegroundColor Cyan
