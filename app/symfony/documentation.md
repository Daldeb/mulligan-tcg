# Configuration API Symfony + Vue.js

## 🏗️ Architecture actuelle

### Setup des serveurs

Notre application utilise une architecture Docker avec plusieurs conteneurs :

- **Symfony (Backend)** : Conteneur `tcg_dev_app` avec PHP-FPM sur port 9000 interne
- **Nginx** : Conteneur `tcg_dev_nginx` qui fait le proxy vers PHP-FPM
- **Vue.js (Frontend)** : Serveur de développement sur port 5173
- **MySQL** : Base de données sur port 3308 externe
- **Redis** : Cache sur port 6381 externe

### URLs d'accès

- **API Symfony** : `http://51.178.27.41:8080/api/` (via Nginx proxy)
- **Frontend Vue.js** : `http://51.178.27.41:5174/` (serveur dev Vite)
- **Adminer** : `http://51.178.27.41:8082/` (gestion BDD)

## 🔧 Configuration réseau

### Nginx (Port 8080)
- Route `/` vers Symfony via FastCGI (PHP-FPM port 9000)
- Route `/api/*` vers les controllers Symfony
- Configuration dans `/infrastructure/nginx/sites/default.conf`

### Symfony Security
- Routes publiques : `/api/(register|login|verify-email|forgot-password|resend-verification)`
- Routes protégées : `/api/*` (nécessitent JWT)
- Configuration dans `config/packages/security.yaml`

## 🚨 Problèmes rencontrés et résolutions

### 1. Serveur Symfony non accessible
**Problème** : `curl localhost:8000` ne fonctionnait pas dans le conteneur
**Cause** : Pas de serveur web dédié, seulement PHP-FPM
**Solution** : Utilisation du proxy Nginx qui route vers PHP-FPM

### 2. Erreur JWT Token sur routes publiques
**Problème** : `{"code":401,"message":"JWT Token not found"}` sur `/api/register`
**Cause** : Ordre incorrect des firewalls dans `security.yaml`
**Solution** : Définir les routes publiques AVANT les routes protégées dans la config

### 3. Erreur RateLimiter
**Problème** : `RateLimiterFactory not found`
**Cause** : Bundle Rate Limiter non installé
**Solution** : Suppression temporaire du rate limiting dans `AuthController::register`

### 4. Erreur colonne 'pseudo' introuvable
**Problème** : `Unknown column 't0.pseudo' in 'field list'`
**Cause** : Entité User mise à jour mais migration non appliquée
**Solution** : 
```bash
sf make:migration
sf doctrine:migrations:migrate
```

### 5. Erreur datetime invalide
**Problème** : `Invalid datetime format: 1292 Incorrect datetime value: '0000-00-00 00:00:00'`
**Cause** : Données existantes avec dates invalides
**Solution** : Nettoyage de la table avant migration
```bash
sf-bash
php bin/console dbal:run-sql "DELETE FROM user"
sf doctrine:migrations:migrate
```

## ✅ Commandes de test API

### Inscription
```bash
curl -X POST http://51.178.27.41:8080/api/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"password123","pseudo":"testuser"}'
```

### Connexion
```bash
curl -X POST http://51.178.27.41:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"password123"}'
```

## 🔑 Commandes utiles

### Gestion Symfony (via alias)
```bash
sf cache:clear                    # Vider le cache
sf doctrine:schema:validate       # Valider le schéma BDD
sf debug:router                   # Lister les routes
sf make:migration                 # Créer une migration
sf doctrine:migrations:migrate    # Appliquer les migrations
```

### Accès conteneurs
```bash
sf-bash                          # Accéder au conteneur Symfony
docker ps                        # Lister les conteneurs
docker logs tcg_dev_nginx        # Logs Nginx
```

## 📁 Fichiers de configuration clés

- `infrastructure/docker-compose.yaml` : Configuration Docker
- `infrastructure/nginx/sites/default.conf` : Configuration Nginx
- `config/packages/security.yaml` : Sécurité Symfony
- `src/Controller/AuthController.php` : API d'authentification
- `src/Entity/User.php` : Entité utilisateur

## 🎯 État actuel

✅ API Symfony fonctionnelle  
✅ Inscription et connexion opérationnelles  
✅ Base de données configurée  
⏳ Intégration frontend Vue.js (en cours)  
⏳ Store Pinia pour gestion d'état (à faire)  
⏳ Header dynamique selon authentification (à faire)