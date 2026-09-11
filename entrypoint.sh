#!/bin/bash
set -e

PORT="${PORT:-8080}"

echo "== NovaTeam entrypoint =="
echo "PORT=$PORT"

# Dejar que PHP lea los secrets desde variables de entorno/railway.
# Si Railway inyecta DATABASE_URL, pasarla tal cual a PHP (db.php la parsea).
if [ -n "$DATABASE_URL" ]; then
  echo "DATABASE_URL detectada -> escribiendo .env"
  cat > /var/www/html/.env <<EOL
DATABASE_URL=${DATABASE_URL}
DB_CHARSET=utf8mb4
APP_NAME=NovaTeam
APP_ENV=production
SESSION_NAME=NOVATEAMSESS
GEMINI_API_KEY=${GEMINI_API_KEY:-}
EOL
else
  echo "Sin DATABASE_URL, se usan variables DB_*"
  cat > /var/www/html/.env <<EOL
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_NAME=${DB_NAME:-novateam_db}
DB_USER=${DB_USER:-root}
DB_PASS=${DB_PASS:-}
DB_CHARSET=utf8mb4
APP_NAME=NovaTeam
APP_ENV=production
SESSION_NAME=NOVATEAMSESS
GEMINI_API_KEY=${GEMINI_API_KEY:-}
EOL
fi

# Crear la base de datos si no existe (Railway no la crea)
if [ -n "$DATABASE_URL" ]; then
  echo "== Asegurando base de datos =="
  php -r '
    $url = getenv("DATABASE_URL");
    $p = parse_url($url);
    $db  = ltrim($p["path"] ?? "", "/");
    $host = $p["host"] ?? "localhost";
    $port = $p["port"] ?? 3306;
    $user = $p["user"] ?? "root";
    $pass = $p["pass"] ?? "";
    if ($db !== "") {
      $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass);
      $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
      echo "Base de datos '$db' lista\n";
    }
  '
fi

# Configurar Apache para escuchar en el puerto de Railway
echo "Configurando Apache en puerto $PORT"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s|<VirtualHost \*:[0-9]*>|<VirtualHost *:${PORT}>|" /etc/apache2/sites-available/000-default.conf

# Bug Railway: deja varios MPM habilitados -> forzamos SOLO mpm_prefork en runtime
echo "== Forzando un unico MPM (mpm_prefork) =="
rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf
rm -f /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf
rm -f /etc/apache2/mods-enabled/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.conf
ln -s ../mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load 2>/dev/null || true
ln -s ../mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf 2>/dev/null || true

apache2ctl configtest 2>&1 || true

echo "== Iniciando Apache =="
exec apache2-foreground