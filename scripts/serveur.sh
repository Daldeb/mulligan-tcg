#!/bin/bash

# TCG HUB - MONITORING SERVEUR
# Usage: ./scripts/serveur.sh

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=== TCG HUB - MONITORING SERVEUR ===${NC}\n"

# SYSTÈME
echo -e "${YELLOW}[SYSTÈME]${NC}"
echo "Uptime: $(uptime -p)"
echo "Load Average: $(cat /proc/loadavg | cut -d' ' -f1-3)"
echo "Kernel: $(uname -r)"

# CPU
echo -e "\n${YELLOW}[CPU]${NC}"
cpu_usage=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
echo "Usage CPU: ${cpu_usage}%"
echo "Cores: $(nproc)"

# MÉMOIRE
echo -e "\n${YELLOW}[MÉMOIRE]${NC}"
memory_info=$(free -h | grep '^Mem:')
total_mem=$(echo $memory_info | awk '{print $2}')
used_mem=$(echo $memory_info | awk '{print $3}')
free_mem=$(echo $memory_info | awk '{print $4}')
echo "Total: $total_mem | Utilisée: $used_mem | Libre: $free_mem"

memory_percent=$(free | grep '^Mem:' | awk '{print int($3/$2 * 100)}')
if [ $memory_percent -gt 80 ]; then
    echo -e "${RED}⚠️  ALERTE: Mémoire élevée (${memory_percent}%)${NC}"
elif [ $memory_percent -gt 60 ]; then
    echo -e "${YELLOW}⚠️  WARNING: Mémoire modérée (${memory_percent}%)${NC}"
else
    echo -e "${GREEN}✅ Mémoire OK (${memory_percent}%)${NC}"
fi

# DISQUE
echo -e "\n${YELLOW}[DISQUE]${NC}"
disk_info=$(df -h / | tail -1)
total_disk=$(echo $disk_info | awk '{print $2}')
used_disk=$(echo $disk_info | awk '{print $3}')
free_disk=$(echo $disk_info | awk '{print $4}')
disk_percent=$(echo $disk_info | awk '{print $5}' | cut -d'%' -f1)

echo "Total: $total_disk | Utilisé: $used_disk | Libre: $free_disk"

if [ $disk_percent -gt 85 ]; then
    echo -e "${RED}⚠️  ALERTE: Disque plein (${disk_percent}%)${NC}"
elif [ $disk_percent -gt 70 ]; then
    echo -e "${YELLOW}⚠️  WARNING: Disque rempli (${disk_percent}%)${NC}"
else
    echo -e "${GREEN}✅ Disque OK (${disk_percent}%)${NC}"
fi

# DOCKER
echo -e "\n${YELLOW}[DOCKER]${NC}"
if systemctl is-active --quiet docker; then
    echo -e "${GREEN}✅ Docker service: Running${NC}"
    
    # Containers
    total_containers=$(docker ps -a | wc -l)
    running_containers=$(docker ps | wc -l)
    echo "Containers: $((running_containers-1))/$((total_containers-1)) running"
    
    # Images
    total_images=$(docker images | wc -l)
    echo "Images: $((total_images-1))"
    
    # Volumes
    total_volumes=$(docker volume ls | wc -l)
    echo "Volumes: $((total_volumes-1))"
    
    # Docker stats
    echo -e "\n${BLUE}Container Resources:${NC}"
    docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}" 2>/dev/null || echo "Aucun container en cours"
else
    echo -e "${RED}❌ Docker service: Not running${NC}"
fi

# SERVICES SYSTÈME
echo -e "\n${YELLOW}[SERVICES SYSTÈME]${NC}"
services=("ssh" "docker" "nginx" "mysql" "redis-server")

for service in "${services[@]}"; do
    if systemctl is-active --quiet $service 2>/dev/null; then
        echo -e "${GREEN}✅ $service: Running${NC}"
    elif systemctl list-units --type=service | grep -q $service; then
        echo -e "${RED}❌ $service: Stopped${NC}"
    else
        echo -e "${YELLOW}⚠️  $service: Not installed${NC}"
    fi
done

# PROCESSUS TOP
echo -e "\n${YELLOW}[TOP PROCESSUS CPU]${NC}"
ps aux --sort=-%cpu | head -6 | awk 'NR==1{print "USER       PID  %CPU %MEM COMMAND"} NR>1{printf "%-10s %5s %4s %4s %s\n", $1, $2, $3, $4, $11}'

# CONNEXIONS RÉSEAU
echo -e "\n${YELLOW}[CONNEXIONS RÉSEAU]${NC}"
connections=$(netstat -an 2>/dev/null | grep ESTABLISHED | wc -l)
echo "Connexions actives: $connections"

# Ports en écoute
echo -e "\n${BLUE}Ports en écoute:${NC}"
netstat -tuln 2>/dev/null | grep LISTEN | awk '{print $4}' | sort -n | uniq

echo -e "\n${GREEN}=== FIN MONITORING SERVEUR ===${NC}"
