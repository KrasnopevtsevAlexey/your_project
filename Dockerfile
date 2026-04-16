# Используем официальный образ PHP с Apache
FROM php:8.3-apache

# Устанавливаем расширения для SQLite
RUN docker-php-ext-install pdo_sqlite

# Включаем mod_rewrite для Apache
RUN a2enmod rewrite

# Устанавливаем рабочую директорию
WORKDIR /var/www/html

# Копируем composer.json (если есть)
COPY composer.json ./

# Если есть composer.lock, копируем его
COPY composer.lock ./

# Устанавливаем composer (опционально)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Копируем все файлы проекта
COPY . .

# Создаем папку для данных с правильными правами
RUN mkdir -p /data && chmod 777 /data

# Настраиваем права
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/public

# Копируем конфигурацию Apache
COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# Открываем порт
EXPOSE 80

# Запускаем Apache
CMD ["apache2-foreground"]
