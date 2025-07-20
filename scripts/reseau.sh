#!/bin/bash

# TCG HUB - MONITORING RÉSEAU
# Usage: ./scripts/reseau.sh

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=== TCG HUB - MONITORING RÉSEAU ===${NC}\n"

# INTERFACES RÉSEAU
echo -e "${YELLOW}[INTERFACES RÉSEAU]${NC}"
interfaces=$(ip link show | grep "state UP" | awk -F': ' '{print $2}')
for interface in $interfaces; do
    ip_addr=$(ip addr show $interface | grep "inet " | awk '{print $2}' | head -1)
    echo -e "${GREEN}✅ $interface: $ip_addr${NC}"
done

# CONNECTIVITÉ EXTERNE
echo -e "\n${YELLOW}[CONNECTIVITÉ EXTERNE]${NC}"
# Test Google DNS
if ping -c 1 8.8.8.8 >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Internet (Google DNS): OK${NC}"
else
    echo -e "${RED}❌ Internet (Google DNS): FAILED${NC}"
fi

# Test résolution DNS
if nslookup google.com >/dev/null 2>&1; then
    echo -e "${GREEN}✅ DNS Resolution: OK${NC}"
else
    echo -e "${RED}❌ DNS Resolution: FAILED${NC}"
fi

# PORTS SERVEUR
echo -e "\n${YELLOW}[PORTS SERVEUR]${NC}"
ports_to_check=("22" "80" "443" "3306" "6379" "8080")

for port in "${ports_to_check[@]}"; do
    if netstat -tuln | grep -q ":$port "; then
        service_name=""
        case $port in
            22) service_name="SSH" ;;
            80) service_name="HTTP" ;;
            443) service_name="HTTPS" ;;
            3306) service_name="MySQL" ;;
            6379) service_name="Redis" ;;
            8080) service_name="PhpMyAdmin" ;;
        esac
        echo -e "${GREEN}✅ Port $port ($service_name): OPEN${NC}"
    else
        echo -e "${RED}❌ Port $port: CLOSED${NC}"
    fi
done

# DOCKER NETWORK
echo -e "\n${YELLOW}[DOCKER NETWORK]${NC}"
if docker network ls | grep -q tcg_network; then
    echo -e "${GREEN}✅ Docker Network tcg_network: EXISTS${NC}"
    
    # Containers dans le réseau
    containers_in_network=$(docker network inspect tcg_network | grep -o '"Name": "[^"]*"' | grep -v tcg_network | wc -l)
    echo "Containers connectés: $containers_in_network"
    
    # Liste des containers
    echo -e "\n${BLUE}Containers dans tcg_network:${NC}"
    docker network inspect tcg_network | grep '"Name"' | grep -v tcg_network | sed 's/.*"Name": "\([^"]*\)".*/- \1/'
else
    echo -e "${RED}❌ Docker Network tcg_network: NOT FOUND${NC}"
fi

# COMMUNICATION INTER-CONTAINERS
echo -e "\n${YELLOW}[COMMUNICATION CONTAINERS]${NC}"

# Test MySQL depuis app
if docker ps | grep -q tcg_app && docker ps | grep -q tcg_mysql; then
    mysql_test=$(docker exec tcg_app sh -c "nc -z mysql 3306" 2>/dev/null && echo "OK" || echo "FAILED")
    if [ "$mysql_test" = "OK" ]; then
        echo -e "${GREEN}✅ App → MySQL: OK${NC}"
    else
        echo -e "${RED}❌ App → MySQL: FAILED${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  App ou MySQL container non démarré${NC}"
fi

# Test Redis depuis app
if docker ps | grep -q tcg_app && docker ps | grep -q tcg_redis; then
    redis_test=$(docker exec tcg_app sh -c "nc -z redis 6379" 2>/dev/null && echo "OK" || echo "FAILED")
    if [ "$redis_test" = "OK" ]; then
        echo -e "${GREEN}✅ App → Redis: OK${NC}"
    else
        echo -e "${RED}❌ App → Redis: FAILED${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  App ou Redis container non démarré${NC}"
fi

# Test Nginx vers App
if docker ps | grep -q tcg_nginx && docker ps | grep -q tcg_app; then
    nginx_test=$(docker exec tcg_nginx sh -c "nc -z app 9000" 2>/dev/null && echo "OK" || echo "FAILED")
    if [ "$nginx_test" = "OK" ]; then
        echo -e "${GREEN}✅ Nginx → App: OK${NC}"
    else
        echo -e "${RED}❌ Nginx → App: FAILED${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Nginx ou App container non démarré${NC}"
fi

# STATISTIQUES RÉSEAU
echo -e "\n${YELLOW}[STATISTIQUES RÉSEAU]${NC}"
# Trafic réseau
rx_bytes=$(cat /sys/class/net/eth0/statistics/rx_bytes 2>/dev/null || echo "0")
tx_bytes=$(cat /sys/class/net/eth0/statistics/tx_bytes 2>/dev/null || echo "0")

rx_mb=$((rx_bytes / 1024 / 1024))
tx_mb=$((tx_bytes / 1024 / 1024))

echo "Trafic reçu: ${rx_mb} MB"
echo "Trafic envoyé: ${tx_mb} MB"

# Connexions actives
connections=$(netstat -an | grep ESTABLISHED | wc -l)
echo "Connexions actives: $connections"

# SÉCURITÉ RÉSEAU
echo -e "\n${YELLOW}[SÉCURITÉ RÉSEAU]${NC}"

# Firewall status
if command -v ufw >/dev/null 2>&1; then
    ufw_status=$(ufw status | head -1 | awk '{print $2}')
    if [ "$ufw_status" = "active" ]; then
        echo -e "${GREEN}✅ UFW Firewall: ACTIVE${NC}"
    else
        echo -e "${YELLOW}⚠️  UFW Firewall: INACTIVE${NC}"
    fi
elif command -v iptables >/dev/null 2>&1; then
    iptables_rules=$(iptables -L | wc -l)
    echo -e "${BLUE}ℹ️  Iptables rules: $iptables_rules${NC}"
else
    echo -e "${RED}❌ Aucun firewall détecté${NC}"
fi

# Fail2ban
if systemctl is-active --quiet fail2ban 2>/dev/null; then
    echo -e "${GREEN}✅ Fail2ban: RUNNING${NC}"
else
    echo -e "${YELLOW}⚠️  Fail2ban: NOT RUNNING${NC}"
fi

# SSH brute force attempts
ssh_attempts=$(grep "Failed password" /var/log/auth.log 2>/dev/null | grep "$(date '+%b %d')" | wc -l)
if [ "$ssh_attempts" -gt 10 ]; then
    echo -e "${RED}⚠️  SSH Failed attempts today: $ssh_attempts${NC}"
elif [ "$ssh_attempts" -gt 0 ]; then
    echo -e "${YELLOW}⚠️  SSH Failed attempts today: $ssh_attempts${NC}"
else
    echo -e "${GREEN}✅ SSH Failed attempts today: $ssh_attempts${NC}"
fi

# APIS EXTERNES
echo -e "\n${YELLOW}[APIS EXTERNES]${NC}"

# Test Mappy API (exemple)
if curl -s --max-time 5 "https://api.mappy.com" >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Mappy API: ACCESSIBLE${NC}"
else
    echo -e "${RED}❌ Mappy API: INACCESSIBLE${NC}"
fi

# Test Pokemon TCG API
if curl -s --max-time 5 "https://api.pokemontcg.io/v2/cards" >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Pokemon TCG API: ACCESSIBLE${NC}"
else
    echo -e "${RED}❌ Pokemon TCG API: INACCESSIBLE${NC}"
fi

# LATENCE
echo -e "\n${YELLOW}[LATENCE RÉSEAU]${NC}"
# Ping vers différentes destinations
destinations=("8.8.8.8" "1.1.1.1" "google.com")

for dest in "${destinations[@]}"; do
    ping_result=$(ping -c 3 $dest 2>/dev/null | tail -1 | awk -F'/' '{print $5}')
    if [ ! -z "$ping_result" ]; then
        ping_ms=$(echo "$ping_result" | cut -d'.' -f1)
        if [ "$ping_ms" -lt 50 ]; then
            echo -e "${GREEN}✅ $dest: ${ping_result}ms${NC}"
        elif [ "$ping_ms" -lt 100 ]; then
            echo -e "${YELLOW}⚠️  $dest: ${ping_result}ms${NC}"
        else
            echo -e "${RED}❌ $dest: ${ping_result}ms${NC}"
        fi
    else
        echo -e "${RED}❌ $dest: TIMEOUT${NC}"
    fi
done

echo -e "\n${GREEN}=== FIN MONITORING RÉSEAU ===${NC}"
