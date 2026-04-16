FROM php:8.3-apache

# Установка SQLite и расширения
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Включаем mod_rewrite
RUN a2enmod rewrite

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Composer зависимости
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Копируем проект
COPY . .

# Права и настройки
RUN mkdir -p /data && chmod 777 /data
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Логи
RUN echo "error_log = /dev/stderr" >> /usr/local/etc/php/conf.d/docker.ini
RUN echo "log_errors = On" >> /usr/local/etc/php/conf.d/docker.ini

EXPOSE 80
CMD ["apache2-foreground"]