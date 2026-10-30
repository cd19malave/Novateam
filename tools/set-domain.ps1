param(
  [Parameter(Mandatory=$true)][string]$BaseUrl,
  [string]$Root = 'C:\xampp\htdocs\novateam'
)

$ErrorActionPreference = 'Stop'
$base = $BaseUrl.TrimEnd('/')
if ($base -notmatch '^https?://') { throw "BaseUrl debe empezar por http:// o https://" }

$robots = @"
User-agent: *
Allow: /$
Allow: /index.php
Allow: /registro.php
Allow: /descargar.php
Allow: /politica-privacidad.html
Allow: /assets/

Disallow: /api/
Disallow: /config/
Disallow: /database/
Disallow: /includes/
Disallow: /uploads/
Disallow: /admin.php
Disallow: /alumnos.php
Disallow: /crear-guia.php
Disallow: /editar-guia.php
Disallow: /estudiante.php
Disallow: /guia.php
Disallow: /leaderboard.php
Disallow: /login.php
Disallow: /logout.php
Disallow: /mensajes.php
Disallow: /notificaciones.php
Disallow: /nueva-clave.php
Disallow: /perfil.php
Disallow: /profesor.php
Disallow: /progreso.php
Disallow: /recuperar.php
Disallow: /tienda.php

Sitemap: $base/sitemap.xml
"@

$today = (Get-Date).ToString('yyyy-MM-dd')
$sitemap = @"
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>$base/</loc>
    <lastmod>$today</lastmod>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>
  <url>
    <loc>$base/registro.php</loc>
    <lastmod>$today</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.6</priority>
  </url>
  <url>
    <loc>$base/descargar.php</loc>
    <lastmod>$today</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.5</priority>
  </url>
  <url>
    <loc>$base/politica-privacidad.html</loc>
    <lastmod>$today</lastmod>
    <changefreq>yearly</changefreq>
    <priority>0.3</priority>
  </url>
</urlset>
"@

[System.IO.File]::WriteAllText((Join-Path $Root 'robots.txt'), ($robots -replace "`r?`n", "`r`n"), (New-Object System.Text.UTF8Encoding($false)))
[System.IO.File]::WriteAllText((Join-Path $Root 'sitemap.xml'), ($sitemap -replace "`r?`n", "`r`n"), (New-Object System.Text.UTF8Encoding($false)))
Write-Host "robots.txt y sitemap.xml apuntan a $base"
