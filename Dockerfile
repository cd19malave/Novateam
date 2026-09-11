FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libzip-dev libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql mysqli mbstring zip intl exif \
    && a2dismod mpm_event || true \
    && a2dismod mpm_worker || true \
    && a2enmod mpm_prefork rewrite headers \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . .

RUN mkdir -p uploads/archivos uploads/perfiles && \
    chown -R www-data:www-data uploads && \
    chmod -R 755 uploads && \
    chmod +x entrypoint.sh

ENV PORT=80

EXPOSE 80

CMD ["/var/www/html/entrypoint.sh"]