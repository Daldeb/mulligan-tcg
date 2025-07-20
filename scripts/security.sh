#!/bin/bash

# TCG HUB - MONITORING SÉCURITÉ
# Usage: ./scripts/security.sh

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=== TCG HUB - MONITORING SÉCURITÉ ===${NC}\n"

# UTILISATEURS ET SESSIONS
echo -e "${YELLOW}[UTILISATEURS ET SESSIONS]${NC}"
# Utilisateurs connectés
current_users=$(who | wc -l)
echo "Sessions actives: $current_users"

# Dernières connexions
echo -e "\n${BLUE}Dernières connexions SSH:${NC}"
last -n 5 | head -5

# Utilisateurs avec shell
echo -e "\n${BLUE}Utilisateurs avec shell:${NC}"
grep -E '/bin/(bash|sh|zsh)' /etc/passwd | cut -d: -f1 | tr '\n' ' '
echo

# PERMISSIONS CRITIQUES
echo -e "\n${YELLOW}[PERMISSIONS CRITIQUES]${NC}"

# Fichiers SUID
suid_files=$(find /usr -perm -4000 -type f 2>/dev/null | wc -l)
echo "Fichiers SUID: $suid_files"

# Permissions SSH
ssh_config_perms=$(stat -c "%a" /etc/ssh/sshd_config 2>/dev/null)
if [ "$ssh_config_perms" = "644" ] || [ "$ssh_config_perms" = "600" ]; then
    echo -e "${GREEN}✅ SSH config permissions: $ssh_config_perms${NC}"
else
    echo -e "${RED}❌ SSH config permissions: $ssh_config_perms${NC}"
fi

# Docker socket permissions
if [ -S /var/run/docker.sock ]; then
    docker_perms=$(stat -c "%a" /var/run/docker.sock)
    echo "Docker socket permissions: $docker_perms"
fi

# CONFIGURATION SSH
echo -e "\n${YELLOW}[CONFIGURATION SSH]${NC}"

# Root login
root_login=$(grep "^PermitRootLogin" /etc/ssh/sshd_config 2>/dev/null | awk '{print $2}')
if [ "$root_login" = "no" ] || [ "$root_login" = "prohibit-password" ]; then
    echo -e "${GREEN}✅ Root login: $root_login${NC}"
else
    echo -e "${RED}❌ Root login: $root_login${NC}"
fi

# Password authentication
password_auth=$(grep "^PasswordAuthentication" /etc/ssh/sshd_config 2>/dev/null | awk '{print $2}')
if [ "$password_auth" = "no" ]; then
    echo -e "${GREEN}✅ Password auth: disabled${NC}"
else
    echo -e "${YELLOW}⚠️  Password auth: enabled${NC}"
fi

# Protocol version
protocol=$(grep "^Protocol" /etc/ssh/sshd_config 2>/dev/null | awk '{print $2}')
if [ "$protocol" = "2" ] || [ -z "$protocol" ]; then
    echo -e "${GREEN}✅ SSH Protocol: 2 (ou défaut)${NC}"
else
    echo -e "${RED}❌ SSH Protocol: $protocol${NC}"
fi

# MOTS DE PASSE
echo -e "\n${YELLOW}[POLITIQUE MOTS DE PASSE]${NC}"

# Comptes sans mot de passe
no_password=$(awk -F: '($2 == "" ) { print $1 }' /etc/shadow 2>/dev/null | wc -l)
if [ "$no_password" -eq 0 ]; then
    echo -e "${GREEN}✅ Comptes sans mot de passe: 0${NC}"
else
    echo -e "${RED}❌ Comptes sans mot de passe: $no_password${NC}"
fi

# Comptes verrouillés
locked_accounts=$(awk -F: '($2 ~ /^!/) { print $1 }' /etc/shadow 2>/dev/null | wc -l)
echo "Comptes verrouillés: $locked_accounts"

# FIREWALL ET RÉSEAU
echo -e "\n${YELLOW}[FIREWALL ET RÉSEAU]${NC}"

# UFW status
if command -v ufw >/dev/null 2>&1; then
    ufw_status=$(ufw status | head -1 | awk '{print $2}')
    if [ "$ufw_status" = "active" ]; then
        echo -e "${GREEN}✅ UFW: ACTIVE${NC}"
        ufw status numbered | grep -E "^\[.*\]" | wc -l | xargs echo "Règles actives:"
    else
        echo -e "${RED}❌ UFW: INACTIVE${NC}"
    fi
fi

# Ports ouverts dangereux
dangerous_ports=("23" "21" "135" "139" "445" "1433" "5432")
echo -e "\n${BLUE}Vérification ports dangereux:${NC}"
for port in "${dangerous_ports[@]}"; do
    if netstat -tuln | grep -q ":$port "; then
        echo -e "${RED}⚠️  Port dangereux ouvert: $port${NC}"
    fi
done

# FAIL2BAN
echo -e "\n${YELLOW}[FAIL2BAN]${NC}"
if systemctl is-active --quiet fail2ban 2>/dev/null; then
    echo -e "${GREEN}✅ Fail2ban: RUNNING${NC}"
    
    # Jails actives
    if command -v fail2ban-client >/dev/null 2>&1; then
        active_jails=$(fail2ban-client status 2>/dev/null | grep "Jail list:" | cut -d: -f2 | tr -d ' \t' | tr ',' ' ')
        echo "Jails actives: $active_jails"
        
        # IPs bannies
        total_banned=0
        for jail in $active_jails; do
            banned=$(fail2ban-client status $jail 2>/dev/null | grep "Currently banned:" | awk '{print $4}')
            total_banned=$((total_banned + banned))
        done
        echo "IPs bannies total: $total_banned"
    fi
else
    echo -e "${RED}❌ Fail2ban: NOT RUNNING${NC}"
fi

# LOGS SÉCURITÉ
echo -e "\n${YELLOW}[LOGS SÉCURITÉ]${NC}"

# Tentatives SSH échouées aujourd'hui
ssh_fails=$(grep "Failed password" /var/log/auth.log 2>/dev/null | grep "$(date '+%b %d')" | wc -l)
if [ "$ssh_fails" -gt 20 ]; then
    echo -e "${RED}⚠️  SSH échecs aujourd'hui: $ssh_fails${NC}"
elif [ "$ssh_fails" -gt 5 ]; then
    echo -e "${YELLOW}⚠️  SSH échecs aujourd'hui: $ssh_fails${NC}"
else
    echo -e "${GREEN}✅ SSH échecs aujourd'hui: $ssh_fails${NC}"
fi

# Connexions réussies aujourd'hui
ssh_success=$(grep "Accepted" /var/log/auth.log 2>/dev/null | grep "$(date '+%b %d')" | wc -l)
echo "SSH connexions réussies: $ssh_success"

# Sudo usage
sudo_usage=$(grep "sudo:" /var/log/auth.log 2>/dev/null | grep "$(date '+%b %d')" | wc -l)
echo "Commandes sudo: $sudo_usage"

# DOCKER SÉCURITÉ
echo -e "\n${YELLOW}[DOCKER SÉCURITÉ]${NC}"

# Containers privilégiés
privileged=$(docker ps --format "table {{.Names}}\t{{.Image}}" --filter "label=privileged=true" 2>/dev/null | wc -l)
if [ "$privileged" -gt 1 ]; then
    echo -e "${RED}⚠️  Containers privilégiés: $((privileged-1))${NC}"
else
    echo -e "${GREEN}✅ Containers privilégiés: 0${NC}"
fi

# Images avec vulnérabilités (si disponible)
if command -v docker >/dev/null 2>&1; then
    total_images=$(docker images -q | wc -l)
    echo "Images Docker: $total_images"
fi

# MISES À JOUR SYSTÈME
echo -e "\n${YELLOW}[MISES À JOUR SYSTÈME]${NC}"

# Paquets à mettre à jour
if command -v apt >/dev/null 2>&1; then
    updates=$(apt list --upgradable 2>/dev/null | wc -l)
    security_updates=$(apt list --upgradable 2>/dev/null | grep -i security | wc -l)
    
    if [ "$security_updates" -gt 0 ]; then
        echo -e "${RED}⚠️  Mises à jour sécurité: $security_updates${NC}"
    else
        echo -e "${GREEN}✅ Mises à jour sécurité: 0${NC}"
    fi
    
    echo "Total mises à jour: $((updates-1))"
fi

# Kernel version vs disponible
current_kernel=$(uname -r)
echo "Kernel actuel: $current_kernel"

# CERTIFICATES SSL
echo -e "\n${YELLOW}[CERTIFICATS SSL]${NC}"
if [ -d "./ssl" ]; then
    ssl_files=$(find ./ssl -name "*.pem" -o -name "*.crt" | wc -l)
    if [ "$ssl_files" -gt 0 ]; then
        echo "Certificats SSL: $ssl_files fichiers"
        # Vérifier expiration (si openssl disponible)
        if command -v openssl >/dev/null 2>&1; then
            for cert in ./ssl/*.{pem,crt}; do
                if [ -f "$cert" ]; then
                    expiry=$(openssl x509 -in "$cert" -noout -enddate 2>/dev/null | cut -d= -f2)
                    if [ ! -z "$expiry" ]; then
                        echo "$(basename $cert): expire le $expiry"
                    fi
                fi
            done
        fi
    else
        echo -e "${YELLOW}⚠️  Aucun certificat SSL trouvé${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Dossier SSL non trouvé${NC}"
fi

# FICHIERS SENSIBLES
echo -e "\n${YELLOW}[FICHIERS SENSIBLES]${NC}"

# .env files permissions
if [ -f ".env" ]; then
    env_perms=$(stat -c "%a" .env)
    if [ "$env_perms" = "600" ] || [ "$env_perms" = "644" ]; then
        echo -e "${GREEN}✅ .env permissions: $env_perms${NC}"
    else
        echo -e "${RED}❌ .env permissions: $env_perms${NC}"
    fi
fi

# Docker secrets
if [ -d "./docker" ]; then
    secret_files=$(find ./docker -name "*secret*" -o -name "*key*" -o -name "*password*" 2>/dev/null | wc -l)
    echo "Fichiers secrets Docker: $secret_files"
fi

echo -e "\n${GREEN}=== FIN MONITORING SÉCURITÉ ===${NC}"
