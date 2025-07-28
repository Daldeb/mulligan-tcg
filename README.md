# 🛠 MULLIGAN TCG — Guide de développement local

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

## 🧰 Fichiers `Makefile` utiles

### 📂 `Makefile` global (racine)

```makefile
sf:
	php -S 127.0.0.1:8000 -t app/symfony/public

front:
	cd app/vuejs && npm run dev
```

### 📂 `Makefile` dans `app/symfony`

```makefile
sf:
	php -S 127.0.0.1:8000 -t public

cache:
	php bin/console cache:clear

logs:
	tail -f var/log/dev.log

migrate:
	php bin/console doctrine:migrations:migrate --no-interaction

fixtures:
	php bin/console doctrine:fixtures:load --no-interaction

jwt:
	mkdir -p config/jwt && \
	openssl genrsa -out config/jwt/private.pem -aes256 4096 && \
	openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
```

---

## 🐳 Travailler avec Docker (local)

```bash
# Lancer le stack Docker local
make up

# Arrêter les containers
make down

# Accéder au container backend
make shell

# Voir les logs Symfony
make logs
```

> Les containers locaux utilisent `tcg_local_app`, `tcg_local_mysql`, etc.

---

## 🔐 Accès Adminer

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

## 🧪 Test de déploiement rapide

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

* 100% **opérationnel en local sans Docker**
* 100% **déployable automatiquement en prod**
* Multi-environnement, multi-machine, avec une stratégie de dev claire et durable

Bon dev 🚀
