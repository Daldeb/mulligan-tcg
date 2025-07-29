FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git unzip zip curl libpq-dev libonig-dev libxml2-dev libzip-dev nodejs npm \
    && docker-php-ext-install pdo pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Ajout du script
COPY infrastructure/docker/php/wait-for-mysql.sh /usr/local/bin/wait-for-mysql.sh
RUN chmod +x /usr/local/bin/wait-for-mysql.sh

WORKDIR /var/www/backend

# EntryPoint pour attendre MySQL avant démarrage
ENTRYPOINT ["wait-for-mysql.sh"]
CMD ["php-fpm"]
