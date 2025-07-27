# 🔌 PORT ALLOCATION - MULLIGAN TCG

## 📋 CONVENTION DÉFINITIVE

### 🔧 DÉVELOPPEMENT (/opt/tcg-dev)
- nginx: 8080 (HTTP), 8444 (HTTPS), 5173 (Vite)
- app: 2222 (SSH)
- mysql: 3308 → 3306
- redis: 6381 → 6379

### 🚀 PRODUCTION (/opt/tcg-hub)  
- nginx: 80 (HTTP), 443 (HTTPS)
- app: 2223 (SSH)
- mysql: 3309 → 3306
- redis: 6382 → 6379

## 🌐 URLS
- DEV: http://51.178.27.41:8080
- PROD: http://51.178.27.41/

## ⚠️ RÈGLE
Ces ports NE DOIVENT JAMAIS changer pour éviter les conflits !
