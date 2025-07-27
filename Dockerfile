# Используем официальный PHP с Apache и PHP 8.2
FROM php:8.2-apache

# Устанавливаем системные зависимости и расширения PHP, необходимые Symfony
RUN apt-get update && apt-get install -y \
    git unzip zip curl libicu-dev libonig-dev libzip-dev libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql intl zip opcache \
    && a2enmod rewrite

# Устанавливаем Composer (официальный способ)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Устанавливаем рабочую директорию
WORKDIR /var/www/html

# Копируем composer файлы и устанавливаем зависимости (кешируем слой)
COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader --no-interaction

# Копируем весь проект
COPY . .

# Права для Apache (если нужно)
RUN chown -R www-data:www-data /var/www/html/var /var/www/html/vendor

# Копируем твой кастомный конфиг Apache, если есть
# COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Открываем порт 80
EXPOSE 80

# Запускаем Apache в фоновом режиме
CMD ["apache2-foreground"]
