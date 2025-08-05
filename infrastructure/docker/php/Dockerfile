FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git unzip zip curl libpq-dev libonig-dev libxml2-dev libzip-dev nodejs npm \
    && docker-php-ext-install pdo pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Ajout du script
COPY docker/php/wait-for-mysql.sh /usr/local/bin/wait-for-mysql.sh
RUN chmod +x /usr/local/bin/wait-for-mysql.sh

# --- AJOUT : copier tout le code Symfony (backend) dans le container
COPY ./app/symfony /var/www/backend

WORKDIR /var/www/backend

# --- AJOUT : installer puppeteer et dépendances JS
RUN npm install --production

RUN chown -R dev:dev /var/www/backend/config/jwt \
 && chmod 600 /var/www/backend/config/jwt/private.pem \
 && chmod 644 /var/www/backend/config/jwt/public.pem

# EntryPoint pour attendre MySQL avant démarrage
ENTRYPOINT ["wait-for-mysql.sh"]
CMD ["php-fpm"]
