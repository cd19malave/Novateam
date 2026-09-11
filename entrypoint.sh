#!/bin/bash
set -e

# Construir variables de BD desde DATABASE_URL (Railway)
# Formato: mysql://user:pass@host:port/dbname
if [ -n "$DATABASE_URL" ]; then
  export DB_HOST=$(echo "$DATABASE_URL" | sed -E 's/^mysql:\/\/([^:]+):([^@]+)@([^:\/]+)(:([0-9]+))?\/([^?]+).*$/\3/')
  export DB_USER=$(echo "$DATABASE_URL" | sed -E 's/^mysql:\/\/([^:]+):([^@]+)@([^:\/]+)(:([0-9]+))?\/([^?]+).*$/\1/')
  export DB_PASS=$(echo "$DATABASE_URL" | sed -E 's/^mysql:\/\/([^:]+):([^@]+)@([^:\/]+)(:([0-9]+))?\/([^?]+).*$/\2/')
  export DB_NAME=$(echo "$DATABASE_URL" | sed -E 's/^mysql:\/\/([^:]+):([^@]+)@([^:\/]+)(:([0-9]+))?\/([^?]+).*$/\6/')
  export DB_PORT=$(echo "$DATABASE_URL" | sed -E 's/^mysql:\/\/([^:]+):([^@]+)@([^:\/]+):?([0-9]+)?\/.*$/\4/')
  [ -z "$DB_PORT" ] && export DB_PORT=3306

  # Escribir .env conn los datos de production
  cat > /var/www/html/.env <<EOF
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
EOF
fi

# Esperar a MySQL si no está listo (máx 90s)
if [ -n "$DB_HOST" ]; then
  for i in $(seq 1 90); do
    if timeout 2 bash -c "echo > /dev/tcp/${DB_HOST}/${DB_PORT:-3306}" 2>/dev/null; then
      break
    fi
    sleep 1
    if [ "$i" -eq 90 ]; then echo "WARN: MySQL no respondió a tiempo, continuando..."; fi
  done
fi

exec apache2-foreground