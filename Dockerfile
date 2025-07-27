FROM php:8.2-apache

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    git unzip zip curl libpq-dev libicu-dev libonig-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql intl zip opcache

# Включаем mod_rewrite
RUN a2enmod rewrite

# Установка Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && chmod +x /usr/local/bin/composer

# Рабочая директория
WORKDIR /var/www/html

# Копируем проект
COPY . .

# Установка зависимостей
RUN /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction

# Права
RUN chown -R www-data:www-data /var/www/html/var

# Кастомный Apache конфиг
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80
