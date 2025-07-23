FROM php:8.3-fpm-alpine

# Install system dependencies + SSH
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
    make \
    openssh \
    nodejs \
    npm

# PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache

# Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Créer l’utilisateur dev
RUN adduser -D dev && echo "dev:dev" | chpasswd

# Préparer l’environnement SSH
RUN mkdir -p /home/dev/.ssh && \
    echo "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAICz/VAeO48DgBWLK+v8qlGogtrs24VCjrv/qKTQU0itk daldeb.mulligan@gmail.com" > /home/dev/.ssh/authorized_keys && \
    chown -R dev:dev /home/dev/.ssh && \
    chmod 700 /home/dev/.ssh && \
    chmod 600 /home/dev/.ssh/authorized_keys

# Clés hôte SSH persistantes
RUN ssh-keygen -A && \
    mkdir -p /etc/ssh/keys && \
    cp /etc/ssh/ssh_host_* /etc/ssh/keys/

# Dossier de travail
WORKDIR /var/www/backend

EXPOSE 9000 22

# Modifier le home directory de dev (Alpine-friendly)
RUN sed -i 's|^dev:.*:.*:.*:[^:]*:[^:]*|dev:x:1000:1000:dev:/var/www:/bin/sh|' /etc/passwd && \
    mkdir -p /var/www && chown dev:dev /var/www

# Lancer PHP-FPM + SSHD en conservant les clés
CMD ["sh", "-c", "cp /etc/ssh/keys/ssh_host_* /etc/ssh/ && php-fpm & /usr/sbin/sshd -D"]

# Autoriser le port forwarding pour VS Code
RUN sed -i '/^AllowTcpForwarding/d' /etc/ssh/sshd_config && \
    echo "AllowTcpForwarding yes" >> /etc/ssh/sshd_config
