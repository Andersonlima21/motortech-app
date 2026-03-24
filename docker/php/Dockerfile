# PHP 8.4-FPM
FROM php:8.4-fpm

ARG DEBIAN_FRONTEND=noninteractive

# Instala pacotes essenciais e extensões PHP
RUN apt-get update && apt-get install -y \
    git curl unzip zip libpq-dev libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip xml \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala extensões necessárias
RUN docker-php-ext-install pdo pdo_mysql

# Instala o Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho (MESMO que o nginx)
WORKDIR /var/www/api_motortech

# Define permissões
RUN mkdir -p /var/www/api_motortech/storage /var/www/api_motortech/bootstrap/cache \
    && chown -R www-data:www-data /var/www/api_motortech/storage /var/www/api_motortech/bootstrap/cache \
    && chmod -R 775 /var/www/api_motortech/storage /var/www/api_motortech/bootstrap/cache

# Expõe porta FPM (para nginx)
EXPOSE 9000

CMD ["php-fpm"]
