FROM php:8.3-fpm-alpine

# Install system dependencies including autoconf
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    mysql-client \
    bash \
    netcat-openbsd \
    autoconf \
    g++ \
    make

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/backend

# Create Symfony user
RUN addgroup -g 1000 -S symfony && \
    adduser -u 1000 -S symfony -G symfony

# Change ownership
RUN chown -R symfony:symfony /var/www

USER symfony

EXPOSE 9000

CMD ["php-fpm"]
