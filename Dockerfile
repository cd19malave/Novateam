# EduNova — Dockerfile para Railway
# PHP 8.2 + Apache + extensiones necesarias

FROM php:8.2-apache

# Instalar extensiones: pdo_mysql, mysqli, mbstring, gd (para avatares), curl, zip, intl
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg-dev libfreetype6-dev \
        libonig-dev libzip-dev libicu-dev libcurl4-openssl-dev pkg-config \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql mysqli mbstring zip intl curl exif \
    && a2enmod rewrite headers \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copiar proyecto a la raíz web de Apache
WORKDIR /var/www/html
COPY . .

# Permisos para cargas de archivos y perfiles
RUN mkdir -p uploads/archivos uploads/perfiles \
    && chown -R www-data:www-data uploads \
    && chmod -R 755 uploads

# Generar .env desde variables de entorno al iniciar
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

CMD ["/usr/local/bin/entrypoint.sh"]