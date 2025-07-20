#!/bin/bash

# TCG HUB - MONITORING FRONTEND
# Usage: ./scripts/front.sh

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=== TCG HUB - MONITORING FRONTEND ===${NC}\n"

# NGINX
echo -e "${YELLOW}[NGINX]${NC}"
if docker ps | grep -q tcg_nginx; then
    nginx_status=$(docker exec tcg_nginx wget --spider --server-response http://localhost/health 2>&1 | grep "HTTP/" | tail -1 | awk '{print $2}')
    if [ "$nginx_status" = "200" ]; then
        echo -e "${GREEN}✅ Nginx: Running (HTTP 200)${NC}"
    else
        echo -e "${RED}❌ Nginx: Error (HTTP $nginx_status)${NC}"
    fi
    
    # Stats Nginx
    echo -e "\n${BLUE}Nginx Stats:${NC}"
    docker exec tcg_nginx ps aux | grep nginx
    docker stats tcg_nginx --no-stream --format "CPU: {{.CPUPerc}} | RAM: {{.MemUsage}}" 2>/dev/null
else
    echo -e "${RED}❌ Container Nginx: Not running${NC}"
fi

# VUE.JS BUILD
echo -e "\n${YELLOW}[VUE.JS FRONTEND]${NC}"
if [ -d "./frontend/dist" ]; then
    dist_size=$(du -sh ./frontend/dist 2>/dev/null | cut -f1)
    dist_files=$(find ./frontend/dist -type f | wc -l)
    echo -e "${GREEN}✅ Build Vue.js: $dist_files fichiers ($dist_size)${NC}"
    
    # Vérifier fichiers essentiels
    if [ -f "./frontend/dist/index.html" ]; then
        echo -e "${GREEN}✅ index.html: Présent${NC}"
    else
        echo -e "${RED}❌ index.html: Manquant${NC}"
    fi
    
    # JS/CSS files
    js_files=$(find ./frontend/dist -name "*.js" | wc -l)
    css_files=$(find ./frontend/dist -name "*.css" | wc -l)
    echo "Fichiers JS: $js_files | Fichiers CSS: $css_files"
else
    echo -e "${RED}❌ Dossier dist: Non trouvé${NC}"
fi

# CONTAINER FRONTEND
echo -e "\n${YELLOW}[CONTAINER FRONTEND]${NC}"
if docker ps | grep -q tcg_frontend; then
    echo -e "${GREEN}✅ Container Frontend: Running${NC}"
    docker stats tcg_frontend --no-stream --format "CPU: {{.CPUPerc}} | RAM: {{.MemUsage}}" 2>/dev/null
else
    echo -e "${RED}❌ Container Frontend: Not running${NC}"
fi

# TEST ENDPOINTS WEB
echo -e "\n${YELLOW}[ENDPOINTS WEB]${NC}"
server_ip="51.178.27.41"

# Test page d'accueil
response_home=$(curl -s -o /dev/null -w "%{http_code}" http://$server_ip/ 2>/dev/null)
if [ "$response_home" = "200" ]; then
    echo -e "${GREEN}✅ Homepage (HTTP $response_home)${NC}"
else
    echo -e "${RED}❌ Homepage (HTTP $response_home)${NC}"
fi

# Test health endpoint
response_health=$(curl -s -o /dev/null -w "%{http_code}" http://$server_ip/health 2>/dev/null)
if [ "$response_health" = "200" ]; then
    echo -e "${GREEN}✅ Health endpoint (HTTP $response_health)${NC}"
else
    echo -e "${RED}❌ Health endpoint (HTTP $response_health)${NC}"
fi

# Test API endpoint
response_api=$(curl -s -o /dev/null -w "%{http_code}" http://$server_ip/api 2>/dev/null)
if [ "$response_api" = "200" ]; then
    echo -e "${GREEN}✅ API endpoint (HTTP $response_api)${NC}"
else
    echo -e "${YELLOW}⚠️  API endpoint (HTTP $response_api) - Normal si Symfony pas encore configuré${NC}"
fi

# TEMPS DE RÉPONSE
echo -e "\n${YELLOW}[PERFORMANCE]${NC}"
response_time=$(curl -s -o /dev/null -w "%{time_total}" http://$server_ip/ 2>/dev/null)
if (( $(echo "$response_time < 1.0" | bc -l) )); then
    echo -e "${GREEN}✅ Temps de réponse: ${response_time}s${NC}"
elif (( $(echo "$response_time < 3.0" | bc -l) )); then
    echo -e "${YELLOW}⚠️  Temps de réponse: ${response_time}s${NC}"
else
    echo -e "${RED}❌ Temps de réponse lent: ${response_time}s${NC}"
fi

# LOGS NGINX
echo -e "\n${YELLOW}[LOGS NGINX RÉCENTS]${NC}"
if docker ps | grep -q tcg_nginx; then
    echo "Dernières requêtes:"
    docker logs tcg_nginx --tail 5 2>/dev/null | grep -E "(GET|POST|PUT|DELETE)" | tail -3
else
    echo "Container Nginx non disponible"
fi

# FICHIERS STATIQUES
echo -e "\n${YELLOW}[ASSETS STATIQUES]${NC}"
if [ -d "./frontend/dist/assets" ]; then
    assets_size=$(du -sh ./frontend/dist/assets 2>/dev/null | cut -f1)
    echo "Taille assets: $assets_size"
else
    echo "Dossier assets non trouvé"
fi

echo -e "\n${GREEN}=== FIN MONITORING FRONTEND ===${NC}"

