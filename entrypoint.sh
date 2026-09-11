#!/bin/bash
set -e

PORT="${PORT:-80}"

if [ -n "$DATABASE_URL" ]; then
  export DB_HOST=$(echo "$DATABASE_URL" | sed -E 's|^mysql://([^:]+):([^@]+)@([^:\/]+):?([0-9]+)?/([^?]+).*|\3|')
  export DB_USER=$(echo "$DATABASE_URL" | sed -E 's|^mysql://([^:]+):([^@]+)@([^:\/]+):?([0-9]+)?/([^?]+).*|\1|')
  export DB_PASS=$(echo "$DATABASE_URL" | sed -E 's|^mysql://([^:]+):([^@]+)@([^:\/]+):?([0-9]+)?/([^?]+).*|\2|')
  export DB_NAME=$(echo "$DATABASE_URL" | sed -E 's|^mysql://([^:]+):([^@]+)@([^:\/]+):?([0-9]+)?/([^?]+).*|\5|')
  export DB_PORT=$(echo "$DATABASE_URL" | sed -E 's|^mysql://([^:]+):([^@]+)@([^:\/]+):?([0-9]+)?/([^?]+).*|\4|')
  [ -z "$DB_PORT" ] && export DB_PORT=3306

  cat > /var/www/html/.env <<EOL
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
DB_CHARSET=utf8mb4
APP_NAME=EduNova
APP_ENV=production
SESSION_NAME=EDUNOVASESS
GEMINI_API_KEY=${GEMINI_API_KEY:-}
EOL
fi

if [ -n "$DB_HOST" ]; then
  for i in $(seq 1 60); do
    if timeout 2 bash -c "echo > /dev/tcp/${DB_HOST}/${DB_PORT:-3306}" 2>/dev/null; then
      echo "MySQL listo"
      break
    fi
    sleep 1
    if [ "$i" -eq 60 ]; then echo "WARN: MySQL timeout, continuando..."; fi
  done
fi

if [ "$PORT" != "80" ]; then
  sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
  sed -i "s|<VirtualHost \*:80>|<VirtualHost *:${PORT}>|" /etc/apache2/sites-available/000-default.conf
fi

exec apache2-foreground