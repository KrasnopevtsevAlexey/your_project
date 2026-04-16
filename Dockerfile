FROM php:8.3-apache

# Устанавливаем расширения
RUN docker-php-ext-install pdo_sqlite

# Включаем mod_rewrite
RUN a2enmod rewrite

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Копируем composer файлы и устанавливаем зависимости
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Копируем все файлы
COPY . .

# Создаем папку для данных
RUN mkdir -p /data && chmod 777 /data

# Настраиваем права
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/public

# Настраиваем DocumentRoot на public (этот метод проще)
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Настройка PHP для логов
RUN echo "error_log = /dev/stderr" >> /usr/local/etc/php/conf.d/docker.ini
RUN echo "log_errors = On" >> /usr/local/etc/php/conf.d/docker.ini

EXPOSE 80
CMD ["apache2-foreground"]