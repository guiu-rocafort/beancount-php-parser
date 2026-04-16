FROM php:8.1-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/app

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Fix Git ownership issues
RUN git config --global --add safe.directory /var/www/app