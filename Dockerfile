FROM php:8.3-apache

# Устанавливаем SQLite3, Git и расширение PDO SQLite
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    git \
    unzip \
    && docker-php-ext-install pdo_sqlite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

COPY . .

RUN mkdir -p /data && chmod 777 /data
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/public

RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

RUN echo "error_log = /dev/stderr" >> /usr/local/etc/php/conf.d/docker.ini
RUN echo "log_errors = On" >> /usr/local/etc/php/conf.d/docker.ini

# Настройка порта для Render
ENV PORT=8000
RUN sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
RUN sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/000-default.conf

EXPOSE ${PORT}
CMD ["apache2-foreground"]
