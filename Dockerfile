# Этап установки Composer (объявлен как отдельный stage)
FROM composer:2 AS composer_stage

# Основной образ
FROM php:8.2-apache

# Установка зависимостей системы и PHP-расширений
RUN apt-get update && apt-get install -y \
    git unzip zip curl libpq-dev libicu-dev libonig-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql intl zip opcache

# Включаем mod_rewrite для Symfony
RUN a2enmod rewrite

# Копируем Composer из первого stage
COPY --from=composer_stage /usr/bin/composer /usr/bin/composer

# Устанавливаем рабочую директорию
WORKDIR /var/www/html

# Копируем проект
COPY . .

# Устанавливаем зависимости Symfony
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Настройка прав
RUN chown -R www-data:www-data /var/www/html/var

# Apache конфигурация (если файл существует)
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Открываем порт
EXPOSE 80
