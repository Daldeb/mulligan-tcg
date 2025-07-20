#!/bin/bash

# TCG HUB - SCRIPT DE SETUP COMPLET
# Usage: ./setup.sh

set -e

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=== TCG HUB - SETUP AUTOMATIQUE ===${NC}\n"

# Vérifier si on est root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}❌ Ce script doit être exécuté en tant que root${NC}"
    exit 1
fi

# Créer les dossiers manquants
echo -e "${YELLOW}[1/6] Création de la structure...${NC}"
mkdir -p scripts
chmod +x scripts/*.sh 2>/dev/null || true

# Créer nginx site config
echo -e "${YELLOW}[2/6] Configuration Nginx...${NC}"
cat > nginx/sites/default.conf << 'EOF'
server {
    listen 80;
    server_name 51.178.27.41 localhost;
    root /var/www/frontend;
    index index.html;

    # Health check endpoint
    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }

    # API routes -> Symfony
    location /api {
        try_files $uri @symfony;
    }

    # Symfony backend
    location @symfony {
        root /var/www/backend/public;
        
        # Pass to PHP-FPM
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        include fastcgi_params;
        
        # Timeout settings
        fastcgi_read_timeout 300;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
    }

    # Static files from Symfony
    location /uploads {
        root /var/www/backend/public;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Vue.js SPA
    location / {
        try_files $uri $uri/ /index.html;
        
        # Cache static assets
        location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
            expires 1y;
            add_header Cache-Control "public, immutable";
        }
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    server_tokens off;
}
EOF

# Créer Redis config
echo -e "${YELLOW}[3/6] Configuration Redis...${NC}"
cat > docker/redis/redis.conf << 'EOF'
port 6379
bind 0.0.0.0
timeout 0
tcp-keepalive 300
maxmemory 128mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
loglevel notice
logfile ""
databases 16
tcp-backlog 511
EOF

# Créer PHP config
echo -e "${YELLOW}[4/6] Configuration PHP...${NC}"
cat > docker/php/php.ini << 'EOF'
[PHP]
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
max_input_vars = 3000

[opcache]
opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1

[Date]
date.timezone = Europe/Paris

[Session]
session.save_handler = redis
session.save_path = "tcp://redis:6379"
session.gc_maxlifetime = 86400
EOF

# Créer Node Dockerfile
echo -e "${YELLOW}[5/6] Configuration Node.js...${NC}"
cat > docker/node/Dockerfile << 'EOF'
FROM node:18-alpine

RUN apk add --no-cache git

WORKDIR /var/www/frontend

COPY frontend/package*.json ./
RUN npm ci || npm install

COPY frontend/ .
RUN npm run build || echo "Build will be done later"

CMD ["tail", "-f", "/dev/null"]
EOF

# Rendre les scripts exécutables
echo -e "${YELLOW}[6/6] Permissions scripts...${NC}"
chmod +x scripts/*.sh 2>/dev/null || true
chmod +x Makefile 2>/dev/null || true

# Test Docker
echo -e "\n${BLUE}Test Docker...${NC}"
if docker --version && docker compose version; then
    echo -e "${GREEN}✅ Docker OK${NC}"
else
    echo -e "${RED}❌ Docker problème${NC}"
    exit 1
fi

# Générer clés JWT si pas existantes
if [ ! -f "config/jwt/private.pem" ]; then
    echo -e "\n${YELLOW}Génération clés JWT...${NC}"
    mkdir -p config/jwt
    openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkcs8
    openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
    echo -e "${GREEN}✅ Clés JWT générées${NC}"
fi

echo -e "\n${GREEN}=== SETUP TERMINÉ ===${NC}"
echo -e "${BLUE}Commandes disponibles:${NC}"
echo "  make install    # Installation complète"
echo "  make dev        # Mode développement"
echo "  make health     # Monitoring complet"
echo "  ./scripts/serveur.sh   # Monitoring serveur"
echo "  ./scripts/front.sh     # Monitoring frontend"
echo "  ./scripts/reseau.sh    # Monitoring réseau"
echo "  ./scripts/security.sh  # Monitoring sécurité"
