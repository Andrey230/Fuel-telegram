FROM php:8.2-apache

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git unzip zip curl libpq-dev libicu-dev libonig-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql intl zip opcache

# Включаем mod_rewrite
RUN a2enmod rewrite

# Установка Composer вручную
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Рабочая директория
WORKDIR /var/www/html

# Копируем проект
COPY . .

# Устанавливаем зависимости Symfony
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Настройка прав
RUN chown -R www-data:www-data /var/www/html/var

# Apache конфиг
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80
