FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git unzip zip curl libpq-dev libicu-dev libonig-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql intl zip opcache

RUN a2enmod rewrite

# Установка Composer (сделаем его сразу исполняемым)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

COPY . .

# Используем php для запуска composer или если правильно установлен — просто composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chown -R www-data:www-data /var/www/html/var

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80
