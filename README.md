# 🔧 MULLIGAN TCG — Guide de développement local

Bienvenue dans le guide de développement du projet **MULLIGAN TCG**. Ce document est une référence centrale pour démarrer, développer, tester et déployer efficacement le projet, que ce soit en local ou sur le serveur distant.

---

## ✅ Environnements & URLs

| Environnement | URL d’accès                                          | Description                                |
| ------------- | ---------------------------------------------------- | ------------------------------------------ |
| **Prod**      | [http://51.178.27.41](http://51.178.27.41)           | Site en ligne avec le frontend Vite buildé |
| **Legacy**    | [http://51.178.27.41:8080](http://51.178.27.41:8080) | Ancienne version du site                   |
| **Adminer**   | [http://51.178.27.41:8084](http://51.178.27.41:8084) | Interface BDD (MySQL)                      |

---

## 🚀 Cloner & Démarrer le projet (hors Docker)

> Requis : PHP 8.3+, Composer, Node.js, npm, MySQL local (si besoin)

```bash
# 1. Cloner le repo
git clone git@github.com:Daldeb/mulligan-tcg.git
cd mulligan-tcg

# 2. Installer les dépendances frontend
cd app/vuejs
npm install

# 3. Installer les dépendances backend
cd ../symfony
composer install

# 4. Lancer les serveurs (dans deux terminaux)
make sf     # Backend Symfony sur http://localhost:8000
make front  # Frontend Vite sur http://localhost:5173
```

---

## 📂 Lancer Symfony avec accès base de données (local/Docker)

Symfony accède à la base de données via Docker. Pour éviter toute erreur (ex: "getaddrinfo for mysql failed"), voici la méthode correcte :

```bash
# 1. Démarrer Docker si ce n’est pas déjà fait
make up

# 2. Entrer dans le container Symfony
make shell

# 3. Lancer le serveur Symfony depuis le container
php -S 0.0.0.0:8000 -t public
```

Tu peux maintenant accéder à [http://localhost:8000](http://localhost:8000) en toute sécurité avec une connexion fonctionnelle à la BDD.

### 🔒 JWT : Initialisation locale

Si tu viens de cloner le repo, génère les clés JWT :

```bash
php bin/console lexik:jwt:generate-keypair
```

Vérifie que la passphrase correspond bien à `JWT_PASSPHRASE` dans `.env`. Les fichiers suivants doivent apparaître :

* `config/jwt/private.pem`
* `config/jwt/public.pem`

### 🚀 Migrations

Toujours depuis `make shell` :

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

Tu peux aussi exécuter d’autres commandes utiles :

```bash
php bin/console doctrine:fixtures:load --no-interaction
php bin/console doctrine:schema:validate
```

---

## 📂 Accès à la base de données locale (via Docker + Adminer)

Lorsque tu travailles **en local avec Docker**, la base de données MySQL tourne dans un container dédié, automatiquement géré par Docker Compose. Pour y accéder :

### ▶️ Lancer l’environnement Docker local

Depuis le dossier `infrastructure` :

```bash
make up
# ou :
docker compose up -d
```

Cela démarre les services suivants :

* `tcg_local_mysql` → base de données
* `tcg_local_app` → backend Symfony
* `tcg_local_adminer` → interface BDD web

### 🌐 Accéder à Adminer

Ouvre [http://localhost:8081](http://localhost:8081) dans ton navigateur.

**Identifiants à utiliser :**

| Champ        | Valeur    |
| ------------ | --------- |
| Système      | MySQL     |
| Serveur      | mysql     |
| Utilisateur  | tcg\_user |
| Mot de passe | tcg\_pass |
| Base         | tcg\_db   |

Ces identifiants sont définis dans `docker-compose.yaml`.

---

## 🔐 Accès Adminer (prod)

* URL : [http://51.178.27.41:8084](http://51.178.27.41:8084)
* Serveur : `mysql`
* Utilisateur : `tcg_prod_user`
* Mot de passe : `tcg_prod_password`
* Base de données : `tcg_prod_db`

---

## 🚀 Déploiement auto (CI/CD)

Chaque push sur `main` déclenche automatiquement :

* Pull du code
* Build du frontend Vite
* Rebuild des containers
* Migration DB Doctrine
* Cache clear

### Marker visible dans `index.html` :

```html
<!-- 🔥 STATIC MARKER: VERSION 9000 -->
```

Pour vérifier que la bonne version est en prod :

```bash
curl -s http://51.178.27.41 | grep "VERSION 9000"
```

---

## 🤪 Test de déploiement rapide

```bash
# Ajouter une modif visible dans App.vue ou AppFooter.vue
# Build localement
cd app/vuejs
npm run build

# Commit & push
git add .
git commit -m "🚀 Test de déploiement auto"
git push

# Vérifier :
curl -s http://51.178.27.41 | grep "VERSION"
```

---

## ✅ Conclusion

Le projet est maintenant :

* 100% **opérationnel en local avec ou sans Docker**
* 100% **déployable automatiquement en prod**
* Multi-environnement, multi-machine, avec une stratégie de dev claire et durable

Bon dev 🚀








# 🔍 MONITORING POST-DÉPLOIEMENT

# =====================================
# 1. État général des containers
# =====================================
echo "📊 État des containers prod :"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep tcg_prod

# =====================================
# 2. Santé des services critiques
# =====================================
echo "🏥 Santé MySQL :"
docker exec tcg_prod_mysql mysql -u tcg_prod_user -ptcg_prod_password -e "SELECT 'MySQL OK' as status;" tcg_prod_db

echo "🔗 Test connectivité App → MySQL :"
docker exec tcg_prod_app php bin/console doctrine:query:sql "SELECT COUNT(*) as user_count FROM user"

# =====================================
# 3. Test JWT fonctionnel
# =====================================
echo "🔐 Test génération JWT :"
docker exec tcg_prod_app php bin/console lexik:jwt:generate-token $(docker exec tcg_prod_mysql mysql -u tcg_prod_user -ptcg_prod_password -se "SELECT email FROM user LIMIT 1;" tcg_prod_db 2>/dev/null | tail -1 || echo "test@example.com")

# =====================================
# 4. Réseaux Docker
# =====================================
echo "🌐 Réseaux utilisés :"
docker network ls | grep -E "(tcg|infra)"

echo "🔌 Containers sur le réseau prod :"
docker network inspect infrastructure_tcg_prod_network --format '{{range .Containers}}{{.Name}} {{end}}'

# =====================================
# 5. Volumes et persistance
# =====================================
echo "💾 Volumes de données :"
docker volume ls | grep -E "(mysql|redis).*prod"

# =====================================
# 6. Test site web
# =====================================
echo "🌐 Test site web :"
curl -s -o /dev/null -w "Status: %{http_code}\n" http://51.178.27.41

echo "🔍 Test API register (sans données) :"
curl -s -o /dev/null -w "API Status: %{http_code}\n" -X POST http://51.178.27.41/api/register

# =====================================
# 7. Logs récents si problème
# =====================================
echo "📋 Logs récents de l'app (si erreur) :"
docker logs tcg_prod_app --tail=10 --since=2m

echo "✅ Monitoring terminé !"