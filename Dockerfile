FROM php:8.3-cli
RUN apt-get update && apt-get install -y git unzip zip libzip-dev libpng-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip gd
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader
COPY . /var/www/html
EXPOSE 80
CMD ["php", "-S", "0.0.0.0:80", "-t", "web"]
