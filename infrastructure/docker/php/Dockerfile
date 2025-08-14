FROM php:8.3-fpm

# Installer les dépendances de base + Chrome et ses dépendances Puppeteer
RUN apt-get update && apt-get install -y \
    git unzip zip curl libpq-dev libonig-dev libxml2-dev libzip-dev nodejs npm \
    # Dépendances Chrome/Puppeteer essentielles
    wget gnupg ca-certificates \
    libnspr4 libnss3 libxss1 libasound2 libxtst6 \
    libgtk-3-0 libgbm1 libdrm2 libxcomposite1 libxdamage1 \
    libxrandr2 libgconf-2-4 libxkbcommon0 libwayland-client0 \
    libatk-bridge2.0-0 libatspi2.0-0 libgtk-3-0 \
    fonts-liberation libappindicator3-1 xdg-utils \
    # Dépendances additionnelles pour stabilité
    libc6 libgcc-s1 libstdc++6 \
    && docker-php-ext-install pdo pdo_mysql

# Installer Google Chrome Stable
RUN wget -q -O - https://dl-ssl.google.com/linux/linux_signing_key.pub | apt-key add - \
    && echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" >> /etc/apt/sources.list.d/google.list \
    && apt-get update \
    && apt-get install -y google-chrome-stable \
    && rm -rf /var/lib/apt/lists/*

# Créer un utilisateur non-root pour Puppeteer (sécurité)
RUN groupadd -r dev && useradd -r -g dev -G audio,video dev \
    && mkdir -p /home/dev/Downloads \
    && chown -R dev:dev /home/dev

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Ajout du script (chemin corrigé depuis la racine du projet)
COPY infrastructure/docker/php/wait-for-mysql.sh /usr/local/bin/wait-for-mysql.sh
RUN chmod +x /usr/local/bin/wait-for-mysql.sh

# --- COPIER LE CODE AVANT LE WORKDIR (chemin corrigé) ---
COPY app/symfony /var/www/backend

# --- INSTALLER LES DEPENDANCES NODE (puppeteer) ---
RUN cd /var/www/backend && npm install --production

# --- CONFIGURATION PUPPETEER ---
# Variable d'environnement pour Puppeteer
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/google-chrome-stable

WORKDIR /var/www/backend

# Créer les dossiers uploads avec bonnes permissions
RUN mkdir -p /var/www/backend/public/uploads/hearthstone/metagame \
    && mkdir -p /var/www/backend/public/uploads/hearthstone/wild_metagame \
    && mkdir -p /var/www/backend/public/uploads/magic/metagame \
    && mkdir -p /var/www/backend/public/uploads/pokemon/metagame \
    && chown -R dev:dev /var/www/backend/public/uploads

# Permissions JWT (si les fichiers existent)
RUN if [ -d "/var/www/backend/config/jwt" ]; then \
        chown -R dev:dev /var/www/backend/config/jwt \
        && chmod 600 /var/www/backend/config/jwt/private.pem \
        && chmod 644 /var/www/backend/config/jwt/public.pem; \
    fi

# Donner les permissions sur tout le backend à l'utilisateur dev
RUN chown -R dev:dev /var/www/backend

# EntryPoint pour attendre MySQL avant démarrage
ENTRYPOINT ["wait-for-mysql.sh"]
CMD ["php-fpm"]